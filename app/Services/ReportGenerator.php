<?php

namespace App\Services;

use App\Models\ExamRegistration;
use App\Models\ExamSession;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TCPDF;

/**
 * ReportGenerator Service
 *
 * Section 2.7
 * - 2.7.1 พิมพ์รายชื่อผู้สอบ (PDF)
 * - 2.7.2 Export ข้อมูลผู้สมัครทั้งหมด (Excel multiple sheets)
 */
class ReportGenerator
{
    /**
     * Clean UTF-8 string to remove invalid characters
     */
    private function cleanUtf8(string $string): string
    {
        // Remove invalid UTF-8 characters
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        
        // Remove control characters except newlines and tabs
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        
        // Ensure it's valid UTF-8
        return mb_check_encoding($string, 'UTF-8') ? $string : '';
    }

    /**
     * Generate PDF รายชื่อผู้สอบ แยกตามสถานที่สอบ (ใช้ TCPDF)
     *
     * Section 2.7.1
     *
     * Filters ที่รองรับ:
     * - branch_id (optional)
     * - exam_session_id (optional)
     *
     * @param  int                  $testLocationId  สถานที่สอบ (required)
     * @param  array<string,mixed>  $filters         ฟิลเตอร์เสริม
     * @return TCPDF
     */
    public function generateExamineeListPDF(int $testLocationId, array $filters = []): TCPDF
    {
        $selectedSession = null;

        $query = ExamRegistration::query()
            ->where('test_location_id', $testLocationId)
            ->where('status', '!=', ExamRegistration::STATUS_CANCELLED)
            ->with([
                'testLocation:id,name,code',
                'examSession:id,year,exam_level,exam_date',
                'examinee:id,user_id,branch_id,pending_score,special_score',
                'examinee.user:id,national_id,rank,first_name,last_name',
                'examinee.branch:id,name,code',
            ]);

        if (! empty($filters['branch_id'])) {
            $query->whereHas('examinee', fn ($q) => $q->where('branch_id', (int) $filters['branch_id']));
        }

        if (! empty($filters['exam_session_id'])) {
            $selectedSession = ExamSession::query()->findOrFail((int) $filters['exam_session_id']);
            $sessionLevel = (string) $selectedSession->exam_level;
            $sessionYear = (int) $selectedSession->year;

            $query->whereHas('examSession', fn ($sessionQuery) => $sessionQuery->where('year', $sessionYear))
                ->where(function ($subQuery) use ($sessionLevel): void {
                    $subQuery->where('exam_level', $sessionLevel)
                        ->orWhere(function ($quotaFallbackQuery) use ($sessionLevel): void {
                            $quotaFallbackQuery->whereNull('exam_level')
                                ->whereHas('positionQuota', fn ($quotaQuery) => $quotaQuery->where('exam_level', $sessionLevel));
                        })
                        ->orWhere(function ($sessionFallbackQuery) use ($sessionLevel): void {
                            $sessionFallbackQuery->whereNull('exam_level')
                                ->whereDoesntHave('positionQuota')
                                ->whereHas('examSession', fn ($sessionQuery) => $sessionQuery->where('exam_level', $sessionLevel));
                        });
                });
        }

        /** @var EloquentCollection<int, ExamRegistration> $rows */
        $rows = $query->get()->sortBy(function (ExamRegistration $registration): string {
            return (string) ($registration->exam_number ?? 'ZZZZZ');
        })->values();

        $firstRow = $rows->first();
        $locationName = $firstRow?->testLocation?->name ?? '-';
        $sessionLabel = $selectedSession?->exam_level_label ?? $firstRow?->examSession?->exam_level_label ?? '-';
        $examDate = $selectedSession?->exam_date?->format('d/m/Y') ?? $firstRow?->examSession?->exam_date?->format('d/m/Y') ?? '-';
        $printedAt = now()->format('d/m/Y H:i');
        $printedBy = Auth::user()?->full_name ?? 'System';

        // แปลงข้อมูลสำหรับแถวตารางใน PDF
        $tableRows = $rows->map(function (ExamRegistration $registration): array {
            $user = $registration->examinee?->user;
            $branch = $registration->examinee?->branch;
            $totalScore = $registration->examinee
                ? ((float) $registration->examinee->pending_score + (float) $registration->examinee->special_score)
                : 0.0;

            // Clean UTF-8 data
            $cleanRank = $user ? $this->cleanUtf8($user->rank) : '';
            $cleanFirstName = $user ? $this->cleanUtf8($user->first_name) : '';
            $cleanLastName = $user ? $this->cleanUtf8($user->last_name) : '';
            $cleanNationalId = $user ? $this->cleanUtf8($user->national_id) : '';
            $cleanBranchName = $branch ? $this->cleanUtf8($branch->name) : '';
            $cleanPosition = $this->cleanUtf8($registration->exam_position_display);

            return [
                'exam_number' => (string) ($registration->exam_number ?? '-'),
                'full_name' => trim($cleanRank . ' ' . $cleanFirstName . ' ' . $cleanLastName),
                'national_id' => (string) $cleanNationalId,
                'branch' => (string) $cleanBranchName,
                'exam_position' => (string) $cleanPosition,
                'total_score' => number_format($totalScore, 2),
            ];
        });

        // Clean location data
        $locationName = $this->cleanUtf8($locationName);
        $sessionLabel = $this->cleanUtf8($sessionLabel);
        $examDate = $this->cleanUtf8($examDate);
        $printedAt = $this->cleanUtf8($printedAt);
        $printedBy = $this->cleanUtf8($printedBy);

        // Create TCPDF instance
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Military Exam System');
        $pdf->SetAuthor('Military Exam System');
        $pdf->SetTitle('รายชื่อผู้สอบ');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(10, 10, 10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font - use a font that supports Thai
        $pdf->SetFont('freeserif', '', 10);
        
        // Build HTML content with debug
        $totalRows = $tableRows->count();
        \Log::info('ReportGenerator: Processing ' . $totalRows . ' rows for PDF');
        
        $html = '
        <style>
            body { font-family: freeserif, sans-serif; font-size: 10px; color: #111827; }
            .title { font-size: 14px; font-weight: bold; margin-bottom: 8px; text-align: center; }
            .meta { margin-bottom: 8px; }
            .meta div { margin: 2px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            th, td { border: 1px solid #d1d5db; padding: 4px; font-size: 9px; }
            th { background: #f3f4f6; text-align: left; font-weight: bold; }
            .text-right { text-align: right; }
            .footer { margin-top: 10px; font-size: 8px; color: #374151; }
            .no-data { text-align: center; font-style: italic; }
        </style>
        <body>
            <div class="title">รายชื่อผู้สอบ (แยกตามสถานที่สอบ)</div>
            
            <div class="meta">
                <div><strong>สถานที่สอบ:</strong> ' . htmlspecialchars($locationName) . '</div>
                <div><strong>วันที่สอบ:</strong> ' . htmlspecialchars($examDate) . '</div>
                <div><strong>ระดับการสอบ:</strong> ' . htmlspecialchars($sessionLabel) . '</div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="10%">หมายเลขสอบ</th>
                        <th width="25%">ยศ ชื่อ-นามสกุล</th>
                        <th width="15%">หมายเลขประจำตัว</th>
                        <th width="15%">เหล่า</th>
                        <th width="20%">ตำแหน่งที่เลือก</th>
                        <th width="15%" class="text-right">คะแนนรวม</th>
                    </tr>
                </thead>
                <tbody>';
        
        if ($tableRows->count() > 0) {
            foreach ($tableRows as $index => $row) {
                // Debug each row
                \Log::info("Row $index: " . json_encode($row, JSON_UNESCAPED_UNICODE));
                
                $html .= '
                    <tr>
                        <td>' . htmlspecialchars($row['exam_number']) . '</td>
                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                        <td>' . htmlspecialchars($row['national_id']) . '</td>
                        <td>' . htmlspecialchars($row['branch']) . '</td>
                        <td>' . htmlspecialchars($row['exam_position']) . '</td>
                        <td class="text-right">' . htmlspecialchars($row['total_score']) . '</td>
                    </tr>';
            }
        } else {
            $html .= '
                    <tr>
                        <td colspan="6" class="no-data">ไม่พบข้อมูลผู้เข้าสอบตามเงื่อนไขที่เลือก</td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <div><strong>จำนวนผู้สอบทั้งหมด:</strong> ' . $tableRows->count() . ' คน</div>
                <div><strong>วันที่พิมพ์รายงาน:</strong> ' . htmlspecialchars($printedAt) . '</div>
                <div><strong>ผู้พิมพ์รายงาน:</strong> ' . htmlspecialchars($printedBy) . '</div>
            </div>
        </body>';
        
        // Debug final HTML length
        \Log::info('Final HTML length: ' . strlen($html));
        
        // Write HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf;
    }

    /**
     * Export ข้อมูลผู้สมัครทั้งหมดของรอบสอบเป็น Excel (multiple sheets)
     *
     * Section 2.7.2
     *
     * Sheets:
     * 1) ผู้สมัครทั้งหมด
     * 2) แยกตามสถานที่สอบ
     * 3) สรุปสถิติ
     *
     * @param  int                  $examSessionId
     * @param  array<string,mixed>  $filters
     * @return BinaryFileResponse
     */
    public function exportAllExaminees(int $examSessionId, array $filters = []): BinaryFileResponse
    {
        $session = ExamSession::query()->findOrFail($examSessionId);
        $sessionLevel = (string) $session->exam_level;
        $sessionYear = (int) $session->year;
        $sessionLabel = (string) $session->exam_level_label;

        /** @var EloquentCollection<int, ExamRegistration> $registrations */
        $registrations = ExamRegistration::query()
            ->where('status', '!=', ExamRegistration::STATUS_CANCELLED)
            ->whereHas('examSession', fn ($query) => $query->where('year', $sessionYear))
            ->where(function ($subQuery) use ($sessionLevel): void {
                $subQuery->where('exam_level', $sessionLevel)
                    ->orWhere(function ($quotaFallbackQuery) use ($sessionLevel): void {
                        $quotaFallbackQuery->whereNull('exam_level')
                            ->whereHas('positionQuota', fn ($quotaQuery) => $quotaQuery->where('exam_level', $sessionLevel));
                    })
                    ->orWhere(function ($sessionFallbackQuery) use ($sessionLevel): void {
                        $sessionFallbackQuery->whereNull('exam_level')
                            ->whereDoesntHave('positionQuota')
                            ->whereHas('examSession', fn ($sessionQuery) => $sessionQuery->where('exam_level', $sessionLevel));
                    });
            })
            ->when(! empty($filters['test_location_id']), fn ($query) => $query->where('test_location_id', (int) $filters['test_location_id']))
            ->when(! empty($filters['branch_id']), fn ($query) => $query->whereHas('examinee', fn ($subQuery) => $subQuery->where('branch_id', (int) $filters['branch_id'])))
            ->with([
                'testLocation:id,name,code',
                'examSession:id,year,exam_level',
                'examinee:id,user_id,branch_id,pending_score,special_score',
                'examinee.user:id,national_id,rank,first_name,last_name',
                'examinee.branch:id,name,code',
                'positionQuota:id,exam_level',
            ])
            ->get()
            ->sortBy([
                fn (ExamRegistration $registration) => (string) ($registration->testLocation?->code ?? '99'),
                fn (ExamRegistration $registration) => (string) ($registration->exam_number ?? 'ZZZZZ'),
                fn (ExamRegistration $registration) => trim(((string) ($registration->examinee?->user?->first_name ?? '')) . ' ' . ((string) ($registration->examinee?->user?->last_name ?? ''))),
            ])
            ->values();

        $fileName = "examinees_export_session_{$examSessionId}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new class($registrations, $sessionLabel) implements WithMultipleSheets {
            /**
             * @param EloquentCollection<int, ExamRegistration> $registrations
             */
            public function __construct(private EloquentCollection $registrations, private string $sessionLabel)
            {
            }

            /**
             * @return array<int, object>
             */
            public function sheets(): array
            {
                $allRows = $this->registrations->map(function (ExamRegistration $registration): array {
                    $user = $registration->examinee?->user;
                    $branch = $registration->examinee?->branch;
                    $location = $registration->testLocation;
                    $session = $registration->examSession;
                    $pendingScore = $registration->examinee?->pending_score ?? 0;
                    $specialScore = $registration->examinee?->special_score ?? 0;
                    $totalScore = $registration->examinee
                        ? ((float) $pendingScore + (float) $specialScore)
                        : 0.0;

                    return [
                        (string) ($registration->exam_number ?? '-'),
                        (string) ($user?->national_id ?? '-'),
                        (string) ($user?->rank ?? ''),
                        (string) ($user?->first_name ?? ''),
                        (string) ($user?->last_name ?? ''),
                        (string) ($branch?->name ?? '-'),
                        (string) ($location?->name ?? '-'),
                        (string) ($session?->year ?? '-'),
                        $this->sessionLabel,
                        (string) $registration->exam_position_display, // เพิ่มตำแหน่งที่เลือก (JSON format)
                        (string) $registration->status,
                        number_format((float) $pendingScore, 2),
                        number_format((float) $specialScore, 2),
                        number_format($totalScore, 2),
                    ];
                })->values();

                $byLocationRows = $this->registrations
                    ->groupBy(fn (ExamRegistration $registration) => (string) ($registration->testLocation?->name ?? 'ไม่ระบุสถานที่สอบ'))
                    ->map(function (Collection $items, string $locationName): array {
                        return [
                            'location_name' => $locationName,
                            'total' => $items->count(),
                            'numbered' => $items->whereNotNull('exam_number')->count(),
                            'waiting_number' => $items->filter(fn (ExamRegistration $registration): bool => $registration->status === ExamRegistration::STATUS_CONFIRMED && empty($registration->exam_number))->count(),
                            'cancelled' => $items->where('status', ExamRegistration::STATUS_CANCELLED)->count(),
                        ];
                    })
                    ->values()
                    ->map(fn (array $row): array => [
                        $row['location_name'],
                        (string) $row['total'],
                        (string) $row['numbered'],
                        (string) $row['waiting_number'],
                        (string) $row['cancelled'],
                    ])
                    ->values();

                $summaryRows = collect([
                    ['จำนวนผู้สมัครทั้งหมด', (string) $this->registrations->count()],
                    ['ยืนยันการสมัครแล้ว', (string) $this->registrations->where('status', ExamRegistration::STATUS_CONFIRMED)->count()],
                    ['ออกหมายเลขแล้ว', (string) $this->registrations->whereNotNull('exam_number')->count()],
                    ['ยืนยันแล้วแต่ยังไม่มีหมายเลขสอบ', (string) $this->registrations->filter(fn (ExamRegistration $registration): bool => $registration->status === ExamRegistration::STATUS_CONFIRMED && empty($registration->exam_number))->count()],
                    ['รอยืนยันการสมัคร', (string) $this->registrations->where('status', ExamRegistration::STATUS_PENDING)->count()],
                    ['ยกเลิก', (string) $this->registrations->where('status', ExamRegistration::STATUS_CANCELLED)->count()],
                ]);

                return [
                    new class($allRows) implements FromCollection, WithHeadings, ShouldAutoSize {
                        public function __construct(private Collection $rows)
                        {
                        }

                        public function collection(): Collection
                        {
                            return $this->rows;
                        }

                        public function headings(): array
                        {
                            return [
                                'หมายเลขสอบ',
                                'หมายเลขประจำตัว',
                                'ยศ',
                                'ชื่อ',
                                'นามสกุล',
                                'เหล่า',
                                'สถานที่สอบ',
                                'ปีการสอบ',
                                'ระดับการสอบ',
                                'ตำแหน่งที่เลือก',
                                'สถานะ',
                                'คะแนนค้างบรรจุ',
                                'คะแนนเพิ่มพิเศษ',
                                'คะแนนรวม',
                            ];
                        }
                    },
                    new class($byLocationRows) implements FromCollection, WithHeadings, ShouldAutoSize {
                        public function __construct(private Collection $rows)
                        {
                        }

                        public function collection(): Collection
                        {
                            return $this->rows;
                        }

                        public function headings(): array
                        {
                            return ['สถานที่สอบ', 'ทั้งหมด', 'ออกหมายเลขแล้ว', 'ยืนยันแล้วแต่ยังไม่มีหมายเลขสอบ', 'ยกเลิก'];
                        }
                    },
                    new class($summaryRows) implements FromCollection, WithHeadings, ShouldAutoSize {
                        public function __construct(private Collection $rows)
                        {
                        }

                        public function collection(): Collection
                        {
                            return $this->rows;
                        }

                        public function headings(): array
                        {
                            return ['รายการสรุป', 'ค่า'];
                        }
                    },
                ];
            }
        }, $fileName);
    }

}

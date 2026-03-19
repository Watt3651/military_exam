<?php

namespace App\Services;

use App\Models\ExamRegistration;
use App\Models\ExamSession;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * Generate PDF รายชื่อผู้สอบ แยกตามสถานที่สอบ
     *
     * Section 2.7.1
     *
     * Filters ที่รองรับ:
     * - branch_id (optional)
     * - exam_session_id (optional)
     *
     * @param  int                  $testLocationId  สถานที่สอบ (required)
     * @param  array<string,mixed>  $filters         ฟิลเตอร์เสริม
     * @return DomPdfWrapper
     */
    public function generateExamineeListPDF(int $testLocationId, array $filters = []): DomPdfWrapper
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

            return [
                'exam_number' => (string) ($registration->exam_number ?? '-'),
                'full_name' => trim(
                    ((string) ($user?->rank ?? '')) . ' ' .
                    ((string) ($user?->first_name ?? '')) . ' ' .
                    ((string) ($user?->last_name ?? ''))
                ),
                'national_id' => (string) ($user?->national_id ?? '-'),
                'branch' => (string) ($branch?->name ?? '-'),
                'exam_position' => (string) $registration->exam_position_display, // เพิ่มตำแหน่งที่เลือก
                'total_score' => number_format($totalScore, 2),
            ];
        });

        return DomPdf::loadView('pdf.examinee-list', [
            'locationName' => $locationName,
            'examDate' => $examDate,
            'examLevelLabel' => $sessionLabel,
            'rows' => $tableRows,
            'printedAt' => $printedAt,
            'printedBy' => $printedBy,
        ])->setPaper('a4', 'portrait');
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

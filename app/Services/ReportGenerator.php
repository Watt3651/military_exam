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
     * - exam_level (optional: sergeant_major|master_sergeant)
     * - exam_session_id (optional)
     *
     * @param  int                  $testLocationId  สถานที่สอบ (required)
     * @param  array<string,mixed>  $filters         ฟิลเตอร์เสริม
     * @return DomPdfWrapper
     */
    public function generateExamineeListPDF(int $testLocationId, array $filters = []): DomPdfWrapper
    {
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

        if (! empty($filters['exam_level'])) {
            $examLevel = (string) $filters['exam_level'];
            $query->whereHas('examSession', fn ($q) => $q->where('exam_level', $examLevel));
        }

        if (! empty($filters['exam_session_id'])) {
            $query->where('exam_session_id', (int) $filters['exam_session_id']);
        }

        /** @var EloquentCollection<int, ExamRegistration> $rows */
        $rows = $query->get()->sortBy(function (ExamRegistration $registration): string {
            return (string) ($registration->exam_number ?? 'ZZZZZ');
        })->values();

        $firstRow = $rows->first();
        $locationName = $firstRow?->testLocation?->name ?? '-';
        $sessionLabel = $firstRow?->examSession?->exam_level_label ?? '-';
        $examDate = $firstRow?->examSession?->exam_date?->format('d/m/Y') ?? '-';
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
     * @param  int  $examSessionId
     * @return BinaryFileResponse
     */
    public function exportAllExaminees(int $examSessionId): BinaryFileResponse
    {
        /** @var EloquentCollection<int, ExamRegistration> $registrations */
        $registrations = ExamRegistration::query()
            ->where('exam_session_id', $examSessionId)
            ->with([
                'testLocation:id,name,code',
                'examSession:id,year,exam_level',
                'examinee:id,user_id,branch_id,pending_score,special_score',
                'examinee.user:id,national_id,rank,first_name,last_name',
                'examinee.branch:id,name,code',
            ])
            ->get();

        $fileName = "examinees_export_session_{$examSessionId}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new class($registrations) implements WithMultipleSheets {
            /**
             * @param EloquentCollection<int, ExamRegistration> $registrations
             */
            public function __construct(private EloquentCollection $registrations)
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
                    $totalScore = $registration->examinee
                        ? ((float) $registration->examinee->pending_score + (float) $registration->examinee->special_score)
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
                        (string) ($session?->exam_level_label ?? '-'),
                        (string) $registration->status,
                        number_format($totalScore, 2),
                    ];
                })->values();

                $byLocationRows = $this->registrations
                    ->groupBy(fn (ExamRegistration $registration) => (string) ($registration->testLocation?->name ?? 'ไม่ระบุสถานที่สอบ'))
                    ->map(function (Collection $items, string $locationName): array {
                        return [
                            'location_name' => $locationName,
                            'total' => $items->count(),
                            'confirmed' => $items->where('status', ExamRegistration::STATUS_CONFIRMED)->count(),
                            'pending' => $items->where('status', ExamRegistration::STATUS_PENDING)->count(),
                            'cancelled' => $items->where('status', ExamRegistration::STATUS_CANCELLED)->count(),
                        ];
                    })
                    ->values()
                    ->map(fn (array $row): array => [
                        $row['location_name'],
                        (string) $row['total'],
                        (string) $row['confirmed'],
                        (string) $row['pending'],
                        (string) $row['cancelled'],
                    ])
                    ->values();

                $summaryRows = collect([
                    ['จำนวนผู้สมัครทั้งหมด', (string) $this->registrations->count()],
                    ['ยืนยันแล้ว', (string) $this->registrations->where('status', ExamRegistration::STATUS_CONFIRMED)->count()],
                    ['รอออกหมายเลข', (string) $this->registrations->where('status', ExamRegistration::STATUS_PENDING)->count()],
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
                                'สถานะ',
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
                            return ['สถานที่สอบ', 'ทั้งหมด', 'ยืนยันแล้ว', 'รอออกหมายเลข', 'ยกเลิก'];
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

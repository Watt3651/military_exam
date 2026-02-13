<?php

namespace App\Livewire\Examinee;

use App\Models\ExamRegistration;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * DownloadExamCard (Section 2.6.1)
 *
 * หน้าสำหรับผู้เข้าสอบดาวน์โหลดบัตรประจำตัวสอบ (PDF)
 */
#[Layout('components.layouts.examinee')]
#[Title('ดาวน์โหลดบัตรประจำตัวสอบ')]
class DownloadExamCard extends Component
{
    public ?ExamRegistration $registration = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isExaminee()) {
            abort(403, 'เฉพาะผู้เข้าสอบ (Examinee) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $examinee = $user->examinee;
        if (! $examinee) {
            $this->errorMessage = 'ไม่พบข้อมูลผู้เข้าสอบของบัญชีนี้';
            return;
        }

        $this->registration = ExamRegistration::query()
            ->where('examinee_id', $examinee->id)
            ->where('status', ExamRegistration::STATUS_CONFIRMED)
            ->whereNotNull('exam_number')
            ->with([
                'examSession:id,year,exam_level,exam_date',
                'testLocation:id,name,code,address',
                'examinee:id,user_id',
                'examinee.user:id,national_id,rank,first_name,last_name',
            ])
            ->orderByDesc('registered_at')
            ->first();

        if (! $this->registration) {
            $this->errorMessage = 'ยังไม่พบรายการสอบที่ยืนยันแล้ว หรือยังไม่มีหมายเลขสอบ';
        }
    }

    public function download(): BinaryFileResponse|IlluminateResponse
    {
        if (! $this->registration) {
            return response('ยังไม่พบบัตรประจำตัวสอบที่สามารถดาวน์โหลดได้', 422);
        }

        $user = $this->registration->examinee?->user;
        $session = $this->registration->examSession;
        $location = $this->registration->testLocation;

        // Optional QR payload: ระบบปลายทางสามารถนำ string นี้ไปสร้าง/ตรวจสอบ QR ได้
        $qrPayload = sprintf(
            'EXAM_CARD|exam_number=%s|national_id=%s|session_id=%d',
            (string) $this->registration->exam_number,
            (string) ($user?->national_id ?? '-'),
            (int) $this->registration->exam_session_id
        );

        $pdf = DomPdf::loadView('pdf.exam-card', [
            'examNumber' => (string) $this->registration->exam_number,
            'fullName' => trim(
                ((string) ($user?->rank ?? '')) . ' ' .
                ((string) ($user?->first_name ?? '')) . ' ' .
                ((string) ($user?->last_name ?? ''))
            ),
            'nationalId' => (string) ($user?->national_id ?? '-'),
            'testLocation' => (string) ($location?->name ?? '-'),
            'examDate' => $session?->exam_date?->format('d/m/Y') ?? '-',
            'examLevel' => $session?->exam_level_label ?? '-',
            'sessionYear' => (string) ($session?->year ?? '-'),
            'qrPayload' => $qrPayload, // optional
            'showQr' => true, // optional toggle
        ])->setPaper('a5', 'landscape');

        return $pdf->download('exam_card_' . $this->registration->exam_number . '.pdf');
    }

    public function render()
    {
        return view('livewire.examinee.download-exam-card');
    }
}

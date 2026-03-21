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

        try {
            // Create simple HTML content
            $html = '<html><body><h1>Exam Card</h1><p>Exam Number: 75001</p><p>This is a test PDF.</p></body></html>';
            
            $pdf = DomPdf::loadHtml($html)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => false,
                    'isHtml5ParserEnabled' => false,
                    'defaultFont' => 'Arial',
                ]);

            // Return direct response instead of Livewire response
            $pdfContent = $pdf->output();
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="exam_card_test.pdf"')
                ->header('Content-Length', strlen($pdfContent));
                
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return response('เกิดข้อผิดพลาดในการสร้างไฟล์ PDF: ' . $e->getMessage(), 500);
        }
    }

    private function generateExamCardHtml(array $data): string
    {
        // Use only ASCII-safe characters for testing
        $examNumber = '75001'; // Hardcode for testing
        $fullName = 'Test User'; // Use English for testing
        $nationalId = '1234567890123';
        $testLocation = 'Test Location';
        $examDate = '01/01/2024';
        $examLevel = 'Level 1';
        $sessionYear = '2024';

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 2px solid #000; padding: 20px; max-width: 600px; margin: 0 auto; }
        .title { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px; }
        .exam-number { font-size: 48px; font-weight: bold; text-align: center; margin: 20px 0; }
        .info { margin: 10px 0; font-size: 16px; }
        .label { font-weight: bold; display: inline-block; width: 120px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">Exam Card</div>
        <div class="exam-number">' . $examNumber . '</div>
        <div class="info"><span class="label">Name:</span> ' . $fullName . '</div>
        <div class="info"><span class="label">ID:</span> ' . $nationalId . '</div>
        <div class="info"><span class="label">Location:</span> ' . $testLocation . '</div>
        <div class="info"><span class="label">Date:</span> ' . $examDate . '</div>
        <div class="info"><span class="label">Level:</span> ' . $examLevel . ' (Year ' . $sessionYear . ')</div>
        <div style="margin-top: 30px; font-size: 12px; text-align: center; color: #666;">
            Please bring this card with your ID card on exam day
        </div>
    </div>
</body>
</html>';
    }

    public function render()
    {
        return view('livewire.examinee.download-exam-card');
    }
}

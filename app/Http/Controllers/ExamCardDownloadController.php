<?php

namespace App\Http\Controllers;

use App\Models\ExamRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCPDF;

class ExamCardDownloadController extends Controller
{
    public function download(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isExaminee()) {
            abort(403, 'เฉพาะผู้เข้าสอบ (Examinee) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $examinee = $user->examinee;
        if (! $examinee) {
            return response('ไม่พบข้อมูลผู้เข้าสอบของบัญชีนี้', 422);
        }

        $registration = ExamRegistration::query()
            ->where('examinee_id', $examinee->id)
            ->where('status', ExamRegistration::STATUS_CONFIRMED)
            ->whereNotNull('exam_number')
            ->with([
                'examSession:id,year,exam_level,exam_date',
                'testLocation:id,name',
                'examinee:id,user_id',
                'examinee.user:id,national_id,rank,first_name,last_name',
            ])
            ->orderByDesc('registered_at')
            ->first();

        if (! $registration) {
            return response('ยังไม่พบรายการสอบที่ยืนยันแล้ว หรือยังไม่มีหมายเลขสอบ', 422);
        }

        try {
            $user = $registration->examinee?->user;
            $session = $registration->examSession;
            $location = $registration->testLocation;

            // Create TCPDF instance
            $pdf = new TCPDF('L', PDF_UNIT, 'A5', true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator('Military Exam System');
            $pdf->SetAuthor('Military Exam System');
            $pdf->SetTitle('บัตรประจำตัวสอบ');
            
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
            $pdf->SetFont('freeserif', '', 14);
            
            // Build content with better layout for A5 landscape
            $html = '
            <style>
                .card { 
                    border: 2px solid #000; 
                    padding: 15px; 
                    width: 100%;
                    height: 100%;
                    box-sizing: border-box;
                }
                .title { 
                    font-size: 20px; 
                    font-weight: bold; 
                    margin-bottom: 15px; 
                    text-align: center;
                }
                .exam-number { 
                    font-size: 36px; 
                    font-weight: bold; 
                    margin: 15px 0; 
                    text-align: center;
                }
                .info-row {
                    display: flex;
                    margin: 8px 0;
                    font-size: 14px; 
                    line-height: 1.3;
                }
                .label { 
                    font-weight: bold; 
                    width: 100px; 
                    flex-shrink: 0;
                }
                .value {
                    flex: 1;
                }
                .footer {
                    margin-top: 20px; 
                    font-size: 10px; 
                    text-align: center; 
                    color: #666;
                    border-top: 1px solid #ccc;
                    padding-top: 10px;
                }
            </style>
            <div class="card">
                <div class="title">บัตรประจำตัวสอบ</div>
                <div class="exam-number">' . $registration->exam_number . '</div>
                <div class="info-row">
                    <span class="label">ชื่อ-นามสกุล:</span>
                    <span class="value">' . ($user ? $user->rank . ' ' . $user->first_name . ' ' . $user->last_name : '-') . '</span>
                </div>
                <div class="info-row">
                    <span class="label">เลขประจำตัว:</span>
                    <span class="value">' . ($user ? $user->national_id : '-') . '</span>
                </div>
                <div class="info-row">
                    <span class="label">สถานที่สอบ:</span>
                    <span class="value">' . ($location ? $location->name : '-') . '</span>
                </div>
                <div class="info-row">
                    <span class="label">วันที่สอบ:</span>
                    <span class="value">' . ($session ? $session->exam_date->format('d/m/Y') : '-') . '</span>
                </div>
                <div class="info-row">
                    <span class="label">ระดับ:</span>
                    <span class="value">' . ($session ? $session->exam_level_label : '-') . ' (ปี ' . ($session ? $session->year : '-') . ')</span>
                </div>
                <div class="footer">
                    กรุณานำบัตรนี้พร้อมบัตรประจำตัวประชาชนมาในวันสอบ
                </div>
            </div>';
            
            // Write HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Close and output PDF document
            $pdfContent = $pdf->Output('', 'S');
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="exam_card_' . $registration->exam_number . '.pdf"')
                ->header('Content-Length', strlen($pdfContent));
                
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return response('เกิดข้อผิดพลาดในการสร้างไฟล์ PDF: ' . $e->getMessage(), 500);
        }
    }
}

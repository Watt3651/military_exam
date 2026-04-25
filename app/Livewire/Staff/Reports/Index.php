<?php

namespace App\Livewire\Staff\Reports;

use App\Models\Branch;
use App\Models\ExamSession;
use App\Models\TestLocation;
use App\Services\ReportGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Http\Response as IlluminateResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('components.layouts.staff')]
#[Title('รายงาน')]
class Index extends Component
{
    public string $reportType = 'examinee_list_pdf';

    public string $test_location_id = '';
    public string $branch_id = '';
    public string $exam_session_id = '';

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $latestSession = ExamSession::query()
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->first();

        if ($latestSession) {
            $this->exam_session_id = (string) $latestSession->id;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'reportType' => ['required', 'in:examinee_list_pdf,all_examinees_excel'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'exam_session_id' => ['nullable', 'exists:exam_sessions,id'],
        ];

        if ($this->reportType === 'examinee_list_pdf') {
            $rules['test_location_id'] = ['required', 'exists:test_locations,id'];
        } else {
            $rules['exam_session_id'] = ['required', 'exists:exam_sessions,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'reportType.required' => 'กรุณาเลือกประเภทรายงาน',
            'test_location_id.required' => 'กรุณาเลือกสถานที่สอบ',
            'test_location_id.exists' => 'สถานที่สอบไม่ถูกต้อง',
            'branch_id.exists' => 'เหล่าไม่ถูกต้อง',
            'exam_session_id.required' => 'กรุณาเลือกรอบสอบ',
            'exam_session_id.exists' => 'รอบสอบไม่ถูกต้อง',
        ];
    }

    public function updatedReportType(string $value): void
    {
        if ($value === 'all_examinees_excel') {
            $this->resetValidation('test_location_id');
        }
    }

    #[Computed]
    public function testLocations(): Collection
    {
        return TestLocation::query()->ordered()->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function branches(): Collection
    {
        return Branch::query()->ordered()->get(['id', 'name', 'code']);
    }

    #[Computed]
    public function examSessions(): Collection
    {
        return ExamSession::query()
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->get(['id', 'year', 'exam_level']);
    }

    public function generate(ReportGenerator $reportGenerator): BinaryFileResponse|IlluminateResponse
    {
        // Debug: Log method call
        Log::info('Reports generate method called');
        
        try {
            $validated = $this->validate();
            Log::info('Validation passed', $validated);
            
            $testLocationId = $validated['test_location_id'] ?? $this->test_location_id;
            $branchId = $validated['branch_id'] ?? $this->branch_id;
            $examSessionId = $validated['exam_session_id'] ?? $this->exam_session_id;

            Log::info("Report type: {$validated['reportType']}");
            Log::info("Test location ID: $testLocationId");
            Log::info("Branch ID: $branchId");
            Log::info("Exam session ID: $examSessionId");

            if ($validated['reportType'] === 'all_examinees_excel') {
                Log::info('Generating Excel report');
                return $reportGenerator->exportAllExaminees(
                    examSessionId: (int) $examSessionId,
                    filters: [
                        'test_location_id' => $testLocationId !== '' ? (int) $testLocationId : null,
                        'branch_id' => $branchId !== '' ? (int) $branchId : null,
                    ],
                );
            }

            Log::info('Generating PDF report');
            
            // Generate PDF using TCPDF
            $pdf = $reportGenerator->generateExamineeListPDF(
                testLocationId: (int) $testLocationId,
                filters: [
                    'branch_id' => $branchId !== '' ? (int) $branchId : null,
                    'exam_session_id' => $examSessionId !== '' ? (int) $examSessionId : null,
                ],
            );

            Log::info('PDF generated successfully');

            // Return direct response instead of Livewire response
            $pdfContent = $pdf->Output('', 'S');
            
            // Clean PDF content to ensure valid UTF-8
            $pdfContent = mb_convert_encoding($pdfContent, 'UTF-8', 'UTF-8');
            
            Log::info('Returning PDF response');
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="examinee_list_' . now()->format('Ymd_His') . '.pdf"')
                ->header('Content-Length', strlen($pdfContent));
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error: ' . $e->getMessage());
            throw $e; // Re-throw validation exceptions to show errors in UI
        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage());
            Log::error('Error trace: ' . $e->getTraceAsString());
            
            // Return error response instead of throwing
            return response('เกิดข้อผิดพลาดในการสร้างไฟล์ PDF: ' . $e->getMessage(), 500);
        }
    }

    public function render()
    {
        return view('livewire.staff.reports.index');
    }
}

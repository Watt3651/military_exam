<?php

namespace App\Livewire\Staff\Reports;

use App\Models\Branch;
use App\Models\ExamSession;
use App\Models\TestLocation;
use App\Services\ReportGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
    public string $exam_level = '';
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
            'exam_level' => ['nullable', 'in:sergeant_major,master_sergeant'],
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
            'exam_level.in' => 'ระดับการสอบไม่ถูกต้อง',
            'exam_session_id.required' => 'กรุณาเลือกรอบสอบ',
            'exam_session_id.exists' => 'รอบสอบไม่ถูกต้อง',
        ];
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
        $validated = $this->validate();

        if ($validated['reportType'] === 'all_examinees_excel') {
            return $reportGenerator->exportAllExaminees((int) $validated['exam_session_id']);
        }

        $pdf = $reportGenerator->generateExamineeListPDF(
            testLocationId: (int) $validated['test_location_id'],
            filters: [
                'branch_id' => $validated['branch_id'] !== '' ? (int) $validated['branch_id'] : null,
                'exam_level' => $validated['exam_level'] !== '' ? $validated['exam_level'] : null,
                'exam_session_id' => $validated['exam_session_id'] !== '' ? (int) $validated['exam_session_id'] : null,
            ],
        );

        return $pdf->download('examinee_list_' . now()->format('Ymd_His') . '.pdf');
    }

    public function render()
    {
        return view('livewire.staff.reports.index');
    }
}

<?php

namespace App\Livewire\Staff\ExamSessions;

use App\Models\ExamSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('สร้างรอบสอบใหม่')]
class Create extends Component
{
    public string $year = '';
    public string $exam_level = ExamSession::LEVEL_SERGEANT_MAJOR;
    public string $registration_start = '';
    public string $registration_end = '';
    public string $exam_date = '';
    public bool $is_active = true;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'year' => [
                'required',
                'integer',
                'min:2500',
                'max:2600',
                Rule::unique('exam_sessions', 'year')
                    ->where(fn ($q) => $q->where('exam_level', $this->exam_level)),
            ],
            'exam_level' => [
                'required',
                Rule::in([ExamSession::LEVEL_SERGEANT_MAJOR, ExamSession::LEVEL_MASTER_SERGEANT]),
            ],
            'registration_start' => ['required', 'date'],
            'registration_end' => ['required', 'date', 'after:registration_start'],
            'exam_date' => ['required', 'date', 'after:registration_end'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'year.required' => 'กรุณาระบุปีการสอบ',
            'year.unique' => 'ปีการสอบและระดับการสอบนี้มีรอบสอบอยู่แล้ว',
            'exam_level.required' => 'กรุณาเลือกระดับการสอบ',
            'registration_start.required' => 'กรุณาระบุวันเริ่มรับสมัคร',
            'registration_end.required' => 'กรุณาระบุวันปิดรับสมัคร',
            'registration_end.after' => 'วันปิดรับสมัครต้องมากกว่าวันเริ่มรับสมัคร',
            'exam_date.required' => 'กรุณาระบุวันสอบ',
            'exam_date.after' => 'วันสอบต้องมากกว่าวันปิดรับสมัคร',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        ExamSession::create([
            'year' => (int) $validated['year'],
            'exam_level' => $validated['exam_level'],
            'registration_start' => $validated['registration_start'],
            'registration_end' => $validated['registration_end'],
            'exam_date' => $validated['exam_date'],
            'is_active' => (bool) $validated['is_active'],
            'is_archived' => false,
        ]);

        session()->flash('success', 'สร้างรอบสอบเรียบร้อยแล้ว');

        $this->reset(['year', 'registration_start', 'registration_end', 'exam_date']);
        $this->exam_level = ExamSession::LEVEL_SERGEANT_MAJOR;
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.staff.exam-sessions.create');
    }
}

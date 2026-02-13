<?php

namespace App\Livewire\Staff\Examinees;

use App\Models\BorderArea;
use App\Models\Branch;
use App\Models\Examinee;
use App\Models\ExamineeEditLog;
use App\Models\ExamRegistration;
use App\Models\TestLocation;
use App\Services\ScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('แก้ไขข้อมูลผู้เข้าสอบ')]
class Edit extends Component
{
    public Examinee $examinee;
    public ?ExamRegistration $latestRegistration = null;

    public Collection $branches;
    public Collection $borderAreas;
    public Collection $testLocations;
    public Collection $editLogs;

    public string $national_id = '';
    public string $rank = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $position = '';
    public string $branch_id = '';
    public string $age = '';
    public string $eligible_year = '';
    public string $suspended_years = '0';
    public string $border_area_id = '';
    public string $test_location_id = '';
    public string $exam_number = '';
    public string $registration_status = ExamRegistration::STATUS_PENDING;
    public string $reason = '';

    public function mount(int $id): void
    {
        /** @var \App\Models\User $staff */
        $staff = Auth::user();
        if (! $staff->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->branches = Branch::query()->orderBy('code')->get(['id', 'code', 'name']);
        $this->borderAreas = BorderArea::query()->orderBy('code')->get(['id', 'code', 'name', 'special_score']);
        $this->testLocations = TestLocation::query()->orderBy('code')->get(['id', 'code', 'name']);

        $this->examinee = Examinee::query()
            ->with(['user', 'examRegistrations' => fn ($q) => $q->orderByDesc('registered_at')])
            ->findOrFail($id);

        $this->latestRegistration = $this->examinee->examRegistrations->first();

        $this->national_id = $this->examinee->user->national_id;
        $this->rank = (string) $this->examinee->user->rank;
        $this->first_name = (string) $this->examinee->user->first_name;
        $this->last_name = (string) $this->examinee->user->last_name;
        $this->email = (string) ($this->examinee->user->email ?? '');
        $this->position = (string) $this->examinee->position;
        $this->branch_id = (string) $this->examinee->branch_id;
        $this->age = (string) $this->examinee->age;
        $this->eligible_year = (string) $this->examinee->eligible_year;
        $this->suspended_years = (string) $this->examinee->suspended_years;
        $this->border_area_id = (string) ($this->examinee->border_area_id ?? '');

        if ($this->latestRegistration) {
            $this->test_location_id = (string) $this->latestRegistration->test_location_id;
            $this->exam_number = (string) ($this->latestRegistration->exam_number ?? '');
            $this->registration_status = (string) $this->latestRegistration->status;
        }

        $this->loadEditLogs();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'rank' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
            'age' => ['required', 'integer', 'min:18', 'max:60'],
            'eligible_year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'suspended_years' => ['required', 'integer', 'min:0', 'max:20'],
            'border_area_id' => ['nullable', 'exists:border_areas,id'],
            'test_location_id' => ['nullable', 'exists:test_locations,id'],
            'exam_number' => ['nullable', 'string', 'max:5'],
            'registration_status' => ['required', 'in:pending,confirmed,cancelled'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'reason.required' => 'กรุณาระบุเหตุผลในการแก้ไข',
            'reason.min' => 'เหตุผลต้องมีอย่างน้อย 5 ตัวอักษร',
            'test_location_id.exists' => 'สถานที่สอบไม่ถูกต้อง',
            'registration_status.in' => 'สถานะการลงทะเบียนไม่ถูกต้อง',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        /** @var \App\Models\User $staff */
        $staff = Auth::user();
        $calculator = new ScoreCalculator();

        DB::transaction(function () use ($validated, $staff, $calculator): void {
            $user = $this->examinee->user;

            $this->logIfChanged('rank', $user->rank, $validated['rank'], $staff->id, $validated['reason']);
            $this->logIfChanged('first_name', $user->first_name, $validated['first_name'], $staff->id, $validated['reason']);
            $this->logIfChanged('last_name', $user->last_name, $validated['last_name'], $staff->id, $validated['reason']);
            $this->logIfChanged('email', (string) ($user->email ?? ''), (string) ($validated['email'] ?? ''), $staff->id, $validated['reason']);

            $user->update([
                'rank' => $validated['rank'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'] ?: null,
            ]);

            $scores = $calculator->calculateAll(
                (int) $validated['eligible_year'],
                (int) $validated['suspended_years'],
                $validated['border_area_id'] ? (int) $validated['border_area_id'] : null
            );

            $this->logIfChanged('position', $this->examinee->position, $validated['position'], $staff->id, $validated['reason']);
            $this->logIfChanged('branch_id', (string) $this->examinee->branch_id, (string) $validated['branch_id'], $staff->id, $validated['reason']);
            $this->logIfChanged('age', (string) $this->examinee->age, (string) $validated['age'], $staff->id, $validated['reason']);
            $this->logIfChanged('eligible_year', (string) $this->examinee->eligible_year, (string) $validated['eligible_year'], $staff->id, $validated['reason']);
            $this->logIfChanged('suspended_years', (string) $this->examinee->suspended_years, (string) $validated['suspended_years'], $staff->id, $validated['reason']);
            $this->logIfChanged('border_area_id', (string) ($this->examinee->border_area_id ?? ''), (string) ($validated['border_area_id'] ?? ''), $staff->id, $validated['reason']);
            $this->logIfChanged('pending_score', (string) $this->examinee->pending_score, (string) $scores['pending_score'], $staff->id, $validated['reason']);
            $this->logIfChanged('special_score', (string) $this->examinee->special_score, (string) $scores['special_score'], $staff->id, $validated['reason']);

            $this->examinee->update([
                'position' => $validated['position'],
                'branch_id' => $validated['branch_id'],
                'age' => $validated['age'],
                'eligible_year' => $validated['eligible_year'],
                'suspended_years' => $validated['suspended_years'],
                'border_area_id' => $validated['border_area_id'] ?: null,
                'pending_score' => $scores['pending_score'],
                'special_score' => $scores['special_score'],
            ]);

            if ($this->latestRegistration) {
                $this->logIfChanged('test_location_id', (string) $this->latestRegistration->test_location_id, (string) ($validated['test_location_id'] ?? ''), $staff->id, $validated['reason']);
                $this->logIfChanged('exam_number', (string) ($this->latestRegistration->exam_number ?? ''), (string) ($validated['exam_number'] ?? ''), $staff->id, $validated['reason']);
                $this->logIfChanged('status', (string) $this->latestRegistration->status, (string) $validated['registration_status'], $staff->id, $validated['reason']);

                $this->latestRegistration->update([
                    'test_location_id' => $validated['test_location_id'] ?: $this->latestRegistration->test_location_id,
                    'exam_number' => $validated['exam_number'] ?: null,
                    'status' => $validated['registration_status'],
                ]);
            }
        });

        $this->reason = '';
        $this->loadEditLogs();
        session()->flash('success', 'บันทึกการแก้ไขข้อมูลผู้เข้าสอบเรียบร้อย');
    }

    private function logIfChanged(string $field, ?string $oldValue, ?string $newValue, int $editedBy, string $reason): void
    {
        $old = trim((string) ($oldValue ?? ''));
        $new = trim((string) ($newValue ?? ''));

        if ($old === $new) {
            return;
        }

        ExamineeEditLog::create([
            'examinee_id' => $this->examinee->id,
            'edited_by' => $editedBy,
            'field_name' => $field,
            'old_value' => $oldValue !== '' ? $oldValue : null,
            'new_value' => $newValue !== '' ? $newValue : null,
            'reason' => $reason,
            'edited_at' => now(),
        ]);
    }

    private function loadEditLogs(): void
    {
        $this->editLogs = ExamineeEditLog::query()
            ->byExaminee($this->examinee->id)
            ->with('editedBy')
            ->latestFirst()
            ->limit(30)
            ->get();
    }

    public function render()
    {
        return view('livewire.staff.examinees.edit');
    }
}

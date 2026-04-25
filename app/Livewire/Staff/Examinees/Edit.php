<?php

namespace App\Livewire\Staff\Examinees;

use App\Models\BorderArea;
use App\Models\Branch;
use App\Models\DataReviewLog;
use App\Models\Examinee;
use App\Models\ExamineeEditLog;
use App\Models\ExamRegistration;
use App\Models\TestLocation;
use App\Models\Unit;
use App\Notifications\DataReviewNotification as NotificationDataReview;
use App\Services\ScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('แก้ไขข้อมูลผู้เข้าสอบ')]
class Edit extends Component
{
    public Examinee $examinee;
    public ?ExamRegistration $latestRegistration = null;

    // ─── Dropdown Data ───
    public Collection $branches;
    public Collection $borderAreas;
    public Collection $testLocations;
    public Collection $units; // เพิ่ม units
    public Collection $editLogs;

    // ─── Suspended Years Options ───
    public array $availableSuspendedYears = [];

    public string $national_id = '';
    public string $rank = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $position = '';
    public string $branch_id = '';
    public string $unit_id = ''; // เพิ่ม unit_id
    public string $age = '';
    public string $eligible_year = '';
    public array $suspended_years = []; // เปลี่ยนเป็น array เก็บปี พ.ศ.
    public string $border_area_id = '';
    public string $test_location_id = '';
    public string $exam_number = '';
    public string $registration_status = ExamRegistration::STATUS_PENDING;
    public string $reason = '';
    public string $reset_password = '';
    public string $reset_password_confirmation = '';
    public bool $auto_generate_password = false;
    public ?string $generated_password = null;

    // ─── Notification Properties ───
    public bool $showNotificationModal = false;
    public string $notificationMessage = '';
    public bool $alertSuccess = false;

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
        $this->units = Unit::ordered()->get(['id', 'code', 'name']); // เพิ่ม units

        $this->examinee = Examinee::query()
            ->with(['user', 'branch', 'unit', 'examRegistrations' => fn ($q) => $q->orderByDesc('registered_at')])
            ->findOrFail($id);

        $this->latestRegistration = $this->examinee->examRegistrations->first();

        $this->national_id = $this->examinee->user->national_id;
        $this->rank = (string) $this->examinee->user->rank;
        $this->first_name = (string) $this->examinee->user->first_name;
        $this->last_name = (string) $this->examinee->user->last_name;
        $this->email = (string) ($this->examinee->user->email ?? '');
        $this->position = (string) $this->examinee->position;
        $this->branch_id = (string) $this->examinee->branch_id;
        $this->unit_id = (string) ($this->examinee->unit_id ?? ''); // เพิ่ม unit_id
        $this->age = (string) $this->examinee->age;
        $this->eligible_year = (string) $this->examinee->eligible_year;
        // แปลง suspended_years เป็น array
        $suspendedYears = $this->examinee->suspended_years ?? [];
        $this->suspended_years = is_array($suspendedYears) ? $suspendedYears : [];
        $this->border_area_id = (string) ($this->examinee->border_area_id ?? '');

        if ($this->latestRegistration) {
            $this->test_location_id = (string) $this->latestRegistration->test_location_id;
            $this->exam_number = (string) ($this->latestRegistration->exam_number ?? '');
            $this->registration_status = (string) $this->latestRegistration->status;
        }

        $this->loadEditLogs();

        // Generate available suspended years options
        $this->generateAvailableSuspendedYears();
    }

    /**
     * สร้างรายการปีที่สามารถเลือกเป็นปีงดบำเหน็จได้
     */
    public function generateAvailableSuspendedYears(): void
    {
        $currentYear = (int) date('Y') + 543;
        $eligibleYear = (int) $this->eligible_year;

        $this->availableSuspendedYears = [];
        if ($eligibleYear > 0 && $eligibleYear <= $currentYear) {
            for ($year = $eligibleYear; $year <= $currentYear; $year++) {
                $yearIndex = $year - $eligibleYear + 1;
                $points = $this->getTierPoints($yearIndex);
                $this->availableSuspendedYears[] = [
                    'year' => $year,
                    'index' => $yearIndex,
                    'points' => $points,
                ];
            }
        }
    }

    /**
     * ดึงคะแนนตาม tier ของปี
     */
    private function getTierPoints(int $yearIndex): int
    {
        if ($yearIndex <= 5) {
            return 2;
        } elseif ($yearIndex <= 10) {
            return 3;
        } elseif ($yearIndex <= 15) {
            return 4;
        } else {
            return 5;
        }
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
            'unit_id' => ['nullable', 'exists:units,id'],
            'age' => ['required', 'integer', 'min:18', 'max:60'],
            'eligible_year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'suspended_years' => ['nullable', 'array'], // เปลี่ยนเป็น array
            'suspended_years.*' => ['integer', 'min:2500', 'max:2600'],
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
            'reset_password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'reset_password.min' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร',
            'reset_password.confirmed' => 'ยืนยันรหัสผ่านใหม่ไม่ตรงกัน',
        ];
    }

    public function updatedAutoGeneratePassword($value): void
    {
        if ($value) {
            $this->reset_password = '';
            $this->reset_password_confirmation = '';
        }
    }

    protected function generatePassword(): string
    {
        return Str::password(12);
    }

    public function save(): void
    {
        // Debug: ตรวจสอบว่า method ถูกเรียกหรือไม่
        error_log('STAFF SAVE METHOD CALLED!');
        
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
                $validated['suspended_years'] ?? [],
                $validated['border_area_id'] ? (int) $validated['border_area_id'] : null
            );

            $this->logIfChanged('position', $this->examinee->position, $validated['position'], $staff->id, $validated['reason']);
            $this->logIfChanged('branch_id', (string) $this->examinee->branch_id, (string) $validated['branch_id'], $staff->id, $validated['reason']);
            $this->logIfChanged('unit_id', (string) ($this->examinee->unit_id ?? ''), (string) ($validated['unit_id'] ?? ''), $staff->id, $validated['reason']);
            $this->logIfChanged('age', (string) $this->examinee->age, (string) $validated['age'], $staff->id, $validated['reason']);
            $this->logIfChanged('eligible_year', (string) $this->examinee->eligible_year, (string) $validated['eligible_year'], $staff->id, $validated['reason']);
            $this->logIfChanged('suspended_years', json_encode($this->examinee->suspended_years ?? []), json_encode($validated['suspended_years'] ?? []), $staff->id, $validated['reason']);
            $this->logIfChanged('border_area_id', (string) ($this->examinee->border_area_id ?? ''), (string) ($validated['border_area_id'] ?? ''), $staff->id, $validated['reason']);
            $this->logIfChanged('pending_score', (string) $this->examinee->pending_score, (string) $scores['pending_score'], $staff->id, $validated['reason']);
            $this->logIfChanged('special_score', (string) $this->examinee->special_score, (string) $scores['special_score'], $staff->id, $validated['reason']);

            // Update Examinee with new data - ใช้วิธี direct update
                $updateResult = DB::table('examinees')
                    ->where('id', $this->examinee->id)
                    ->update([
                        'position' => $validated['position'],
                        'branch_id' => $validated['branch_id'],
                        'unit_id' => $validated['unit_id'] ?? null,
                        'age' => $validated['age'],
                        'eligible_year' => $validated['eligible_year'],
                        'suspended_years' => json_encode($validated['suspended_years'] ?? []),
                        'border_area_id' => $validated['border_area_id'] ?: null,
                        'pending_score' => $scores['pending_score'],
                        'special_score' => $scores['special_score'],
                        'updated_at' => now(),
                    ]);

                error_log('Staff Direct Update Result: ' . $updateResult);

            // Debug: ตรวจสอบว่าบันทึกสำเร็จหรือไม่
            error_log('Staff Edit Debug: ' . json_encode([
                'examinee_id' => $this->examinee->id,
                'validated_unit_id' => $validated['unit_id'] ?? 'NULL',
                'update_result' => 'success',
            ]));

            // Update properties with fresh data after save
            $this->unit_id = (string) ($validated['unit_id'] ?? '');

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

    public function resetPassword(): void
    {
        $rules = [];

        if (! $this->auto_generate_password) {
            $rules['reset_password'] = [
                'required',
                'string',
                'confirmed',
                'min:8',
                Password::defaults(),
            ];
        }

        if ($rules !== []) {
            $this->validate($rules);
        }

        $password = $this->auto_generate_password
            ? $this->generatePassword()
            : $this->reset_password;

        $this->examinee->user->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        $this->generated_password = $password;
        $this->reset_password = '';
        $this->reset_password_confirmation = '';
        $this->resetValidation(['reset_password', 'reset_password_confirmation']);

        session()->flash('success', 'รีเซ็ตรหัสผ่านผู้สมัครสอบเรียบร้อย');
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

    /*
    |--------------------------------------------------------------------------
    | Notification Methods
    |--------------------------------------------------------------------------
    */

    /**
     * แสดง modal ส่งแจ้งเตือน
     */
    public function openNotificationModal(): void
    {
        // Simple debug - set property directly
        $this->showNotificationModal = true;
        $this->notificationMessage = 'ทดสอบการส่งแจ้งเตือน';
        
        // Force re-render
        $this->dispatch('refresh-component');
    }

    /**
     * ปิด modal ส่งแจ้งเตือน
     */
    public function closeNotificationModal(): void
    {
        $this->showNotificationModal = false;
        $this->notificationMessage = '';
    }

    /**
     * ส่งแจ้งเตือนให้ผู้สมัคร
     */
    public function sendNotification(): void
    {
        $this->validate([
            'notificationMessage' => ['required', 'string', 'max:500'],
        ], [
            'notificationMessage.required' => 'กรุณาระบุข้อความแจ้งเตือน',
            'notificationMessage.max' => 'ข้อความแจ้งเตือนต้องไม่เกิน 500 ตัวอักษร',
        ]);

        /** @var \App\Models\User $staff */
        $staff = Auth::user();

        try {
            DB::transaction(function () use ($staff) {
                // Debug: ตรวจสอบข้อมูล
                \Log::info('Sending notification', [
                    'examinee_id' => $this->examinee->id,
                    'examinee_user_id' => $this->examinee->user_id,
                    'staff_id' => $staff->id,
                    'message' => $this->notificationMessage,
                ]);

                // 1. สร้าง notification
                $this->examinee->user->notify(
                    new NotificationDataReview($this->notificationMessage, $staff)
                );

                \Log::info('Notification sent successfully');

                // 2. บันทึก log
                DataReviewLog::create([
                    'examinee_id' => $this->examinee->id,
                    'staff_id' => $staff->id,
                    'message' => $this->notificationMessage,
                    'status' => 'pending',
                ]);

                \Log::info('Data review log created');
            });

            $this->closeNotificationModal();
            
            // Flash message สำหรับแสดงในหน้า view
            session()->flash('notification_sent', 'ส่งแจ้งเตือนให้ผู้สมัครเรียบร้อยแล้ว');
            
            // Set alert property สำหรับ Livewire
            $this->alertSuccess = true;

        } catch (\Exception $e) {
            \Log::error('Failed to send notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('swal:error', 'เกิดข้อผิดพลาด', 'ไม่สามารถส่งแจ้งเตือนได้ กรุณาลองใหม่อีกครั้ง');
        }
    }

    public function render()
    {
        return view('livewire.staff.examinees.edit');
    }
}

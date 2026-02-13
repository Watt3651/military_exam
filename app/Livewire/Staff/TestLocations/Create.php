<?php

namespace App\Livewire\Staff\TestLocations;

use App\Models\TestLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('เพิ่มสถานที่สอบ')]
class Create extends Component
{
    public string $name = '';
    public string $code = '';
    public string $address = '';
    public string $capacity = '0';
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
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'size:1',
                'regex:/^[1-9]$/',
                Rule::unique('test_locations', 'code'),
            ],
            'address' => ['nullable', 'string'],
            'capacity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อสถานที่สอบ',
            'code.required' => 'กรุณากรอกรหัสสถานที่',
            'code.size' => 'รหัสสถานที่ต้องมี 1 หลัก',
            'code.regex' => 'รหัสสถานที่ต้องเป็นตัวเลข 1-9',
            'code.unique' => 'รหัสสถานที่นี้ถูกใช้งานแล้ว',
            'capacity.required' => 'กรุณาระบุจำนวนที่รับได้',
            'capacity.integer' => 'จำนวนที่รับได้ต้องเป็นตัวเลขจำนวนเต็ม',
            'capacity.min' => 'จำนวนที่รับได้ต้องไม่น้อยกว่า 0',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        TestLocation::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address' => $validated['address'] !== '' ? $validated['address'] : null,
            'capacity' => (int) $validated['capacity'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        session()->flash('success', 'เพิ่มสถานที่สอบเรียบร้อย');

        $this->reset(['name', 'code', 'address']);
        $this->capacity = '0';
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.staff.test-locations.create');
    }
}

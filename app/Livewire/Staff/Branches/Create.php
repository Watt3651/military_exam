<?php

namespace App\Livewire\Staff\Branches;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('เพิ่มเหล่า')]
class Create extends Component
{
    public string $name = '';
    public string $code = '';
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
                Rule::unique('branches', 'code'),
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อเหล่า',
            'code.required' => 'กรุณากรอกรหัสเหล่า',
            'code.size' => 'รหัสเหล่าต้องมี 1 หลัก',
            'code.regex' => 'รหัสเหล่าต้องเป็นตัวเลข 1-9',
            'code.unique' => 'รหัสเหล่านี้ถูกใช้งานแล้ว',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Branch::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'is_active' => (bool) $validated['is_active'],
        ]);

        session()->flash('success', 'เพิ่มเหล่าเรียบร้อย');
        $this->reset(['name', 'code']);
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.staff.branches.create');
    }
}

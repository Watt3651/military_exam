<?php

namespace App\Livewire\Staff\Units;

use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('สร้างสังกัดใหม่')]
class Create extends Component
{
    public string $name = '';
    public string $code = '';
    public bool $is_active = true;
    public string $description = '';

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
            'code' => ['required', 'string', 'max:50', 'unique:units,code'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Unit::create($validated);

        session()->flash('success', 'สร้างสังกัดเรียบร้อยแล้ว');

        $this->redirectRoute('staff.units.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.staff.units.create');
    }
}

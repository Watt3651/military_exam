<?php

namespace App\Livewire\Staff\BorderAreas;

use App\Models\BorderArea;
use App\Services\BorderAreaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('เพิ่มพื้นที่ชายแดน')]
class Create extends Component
{
    public string $code = '';
    public string $name = '';
    public string $special_score = '0.00';
    public string $description = '';
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
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('border_areas', 'code'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'special_score' => [
                'required',
                'numeric',
                'min:0',
                'max:99.99',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'code.required' => 'กรุณากรอกรหัสพื้นที่',
            'code.unique' => 'รหัสพื้นที่นี้ถูกใช้งานแล้ว',
            'name.required' => 'กรุณากรอกชื่อพื้นที่',
            'special_score.required' => 'กรุณากรอกคะแนนพิเศษ',
            'special_score.numeric' => 'คะแนนพิเศษต้องเป็นตัวเลข',
            'special_score.min' => 'คะแนนพิเศษต้องไม่น้อยกว่า 0',
            'special_score.max' => 'คะแนนพิเศษต้องไม่เกิน 99.99',
        ];
    }

    public function save(BorderAreaService $borderAreaService): void
    {
        $validated = $this->validate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ใช้ service เดียวกับหน้า edit เพื่อให้ business logic/history เป็นมาตรฐานเดียวกัน
        $borderArea = $borderAreaService->updateWithHistory(
            borderArea: new BorderArea(),
            data: [
                'code' => $validated['code'],
                'name' => $validated['name'],
                'special_score' => (float) $validated['special_score'],
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'is_active' => (bool) $validated['is_active'],
                'created_by' => $user->id,
            ],
            userId: $user->id,
            reason: null,
        );

        session()->flash('success', "สร้างพื้นที่ชายแดนสำเร็จ ({$borderArea->code} - {$borderArea->name}) และบันทึกประวัติคะแนนแล้ว");

        $this->reset(['code', 'name', 'description']);
        $this->special_score = '0.00';
        $this->is_active = true;
    }

    public function render()
    {
        return view('livewire.staff.border-areas.create');
    }
}

<?php

namespace App\Livewire\Staff\BorderAreas;

use App\Models\BorderArea;
use App\Models\BorderAreaScoreHistory;
use App\Services\BorderAreaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.staff')]
#[Title('แก้ไขพื้นที่ชายแดน')]
class Edit extends Component
{
    public BorderArea $borderArea;
    public Collection $scoreLogs;

    public string $code = '';
    public string $name = '';
    public string $special_score = '0.00';
    public string $description = '';
    public bool $is_active = true;
    public ?string $reason = null;

    public function mount(int $id): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $this->borderArea = BorderArea::findOrFail($id);

        $this->code = $this->borderArea->code;
        $this->name = $this->borderArea->name;
        $this->special_score = number_format((float) $this->borderArea->special_score, 2, '.', '');
        $this->description = (string) ($this->borderArea->description ?? '');
        $this->is_active = (bool) $this->borderArea->is_active;
        $this->loadScoreLogs();
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
                Rule::unique('border_areas', 'code')->ignore($this->borderArea->id),
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
            'reason' => [
                'nullable',
                'string',
                'max:500',
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
            'reason.max' => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ];
    }

    public function save(BorderAreaService $borderAreaService): void
    {
        $validated = $this->validate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $oldScore = (float) $this->borderArea->special_score;
        $newScore = (float) $validated['special_score'];
        $scoreChanged = $oldScore !== $newScore;

        if ($scoreChanged && empty(trim((string) $validated['reason']))) {
            $this->addError('reason', 'กรุณาระบุเหตุผลในการเปลี่ยนคะแนนพิเศษ');
            return;
        }

        $updated = $borderAreaService->updateWithHistory(
            borderArea: $this->borderArea,
            data: [
                'code' => $validated['code'],
                'name' => $validated['name'],
                'special_score' => $newScore,
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'is_active' => (bool) $validated['is_active'],
            ],
            userId: $user->id,
            reason: $scoreChanged ? ($validated['reason'] ?: null) : null,
        );

        $this->borderArea = $updated;
        $this->special_score = number_format((float) $updated->special_score, 2, '.', '');
        $this->loadScoreLogs();

        $message = $scoreChanged
            ? 'บันทึกข้อมูลเรียบร้อย และบันทึกประวัติการเปลี่ยนคะแนนแล้ว'
            : 'บันทึกข้อมูลเรียบร้อย';

        session()->flash('success', $message);
        $this->reason = null;
    }

    private function loadScoreLogs(): void
    {
        $this->scoreLogs = BorderAreaScoreHistory::query()
            ->where('border_area_id', $this->borderArea->id)
            ->with('changedBy')
            ->latestFirst()
            ->limit(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.staff.border-areas.edit');
    }
}

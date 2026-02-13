<?php

namespace App\Livewire\Staff\PositionQuotas;

use App\Models\ExamSession;
use App\Models\PositionQuota;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.staff')]
#[Title('จัดการอัตราที่เปิดสอบ')]
class Manage extends Component
{
    use WithPagination;

    public ?int $exam_session_id = null;
    public string $position_name = '';
    public ?int $quota_count = null;

    public ?int $editingId = null;
    public string $search = '';
    public int $perPage = 10;

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user->isStaff()) {
            abort(403, 'เฉพาะเจ้าหน้าที่ (Staff) เท่านั้นที่สามารถเข้าถึงหน้านี้ได้');
        }

        $defaultSession = ExamSession::query()
            ->where('is_archived', false)
            ->orderByDesc('year')
            ->orderByDesc('exam_level')
            ->first();

        if ($defaultSession) {
            $this->exam_session_id = $defaultSession->id;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedExamSessionId(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'exam_session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'position_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('position_quotas', 'position_name')
                    ->where(fn ($query) => $query->where('exam_session_id', $this->exam_session_id))
                    ->ignore($this->editingId),
            ],
            'quota_count' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'exam_session_id.required' => 'กรุณาเลือกรอบสอบ',
            'exam_session_id.exists' => 'ไม่พบรอบสอบที่เลือก',
            'position_name.required' => 'กรุณากรอกตำแหน่ง',
            'position_name.unique' => 'ตำแหน่งนี้มีในรอบสอบที่เลือกแล้ว',
            'quota_count.required' => 'กรุณากรอกจำนวนอัตรา',
            'quota_count.integer' => 'จำนวนอัตราต้องเป็นตัวเลขจำนวนเต็ม',
            'quota_count.min' => 'จำนวนอัตราต้องมากกว่าหรือเท่ากับ 0',
        ];
    }

    #[Computed]
    public function examSessions(): Collection
    {
        return ExamSession::query()
            ->orderByDesc('year')
            ->orderBy('exam_level')
            ->get();
    }

    #[Computed]
    public function quotas(): LengthAwarePaginator
    {
        $query = PositionQuota::query()
            ->with(['examSession'])
            ->withCount([
                'examRegistrations as registered_count' => fn ($q) => $q->where('status', '!=', 'cancelled'),
            ])
            ->when($this->exam_session_id, fn ($q) => $q->where('exam_session_id', $this->exam_session_id))
            ->when(trim($this->search) !== '', function ($q): void {
                $keyword = trim($this->search);
                $q->where('position_name', 'like', "%{$keyword}%");
            })
            ->orderBy('position_name');

        return $query->paginate($this->perPage);
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $quota = PositionQuota::findOrFail($this->editingId);
            $quota->update($validated);
            session()->flash('success', 'อัปเดตอัตราที่เปิดสอบเรียบร้อย');
        } else {
            PositionQuota::create($validated);
            session()->flash('success', 'เพิ่มอัตราที่เปิดสอบเรียบร้อย');
        }

        $this->resetForm();
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $quota = PositionQuota::findOrFail($id);
        $this->editingId = $quota->id;
        $this->exam_session_id = $quota->exam_session_id;
        $this->position_name = $quota->position_name;
        $this->quota_count = $quota->quota_count;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $quota = PositionQuota::query()
            ->withCount([
                'examRegistrations as registered_count' => fn ($q) => $q->where('status', '!=', 'cancelled'),
            ])
            ->findOrFail($id);

        if ($quota->registered_count > 0) {
            session()->flash('error', 'ไม่สามารถลบได้ เนื่องจากมีผู้สมัครใช้อัตรานี้แล้ว');
            return;
        }

        $quota->delete();
        session()->flash('success', 'ลบอัตราที่เปิดสอบเรียบร้อย');
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $currentSessionId = $this->exam_session_id;
        $this->reset(['editingId', 'position_name', 'quota_count']);
        $this->exam_session_id = $currentSessionId;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.staff.position-quotas.manage');
    }
}

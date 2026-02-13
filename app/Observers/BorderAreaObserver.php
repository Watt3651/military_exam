<?php

namespace App\Observers;

use App\Models\BorderArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * BorderAreaObserver
 *
 * จัดการ event ของ BorderArea model เพื่อบันทึก log ก่อนอัปเดตและตอนลบข้อมูล
 */
class BorderAreaObserver
{
    /**
     * Handle the BorderArea "updating" event.
     *
     * บันทึกค่าที่เปลี่ยนแปลงก่อน update ลงฐานข้อมูลจริง
     */
    public function updating(BorderArea $borderArea): void
    {
        $dirty = $borderArea->getDirty();

        // ถ้าไม่มีข้อมูลที่เปลี่ยนจริง ไม่ต้อง log
        if ($dirty === []) {
            return;
        }

        $changes = [];
        foreach ($dirty as $field => $newValue) {
            $changes[$field] = [
                'old' => $borderArea->getOriginal($field),
                'new' => $newValue,
            ];
        }

        Log::info('BorderArea updating', [
            'border_area_id' => $borderArea->id,
            'code' => $borderArea->code,
            'name' => $borderArea->name,
            'updated_by' => Auth::id(),
            'changes' => $changes,
        ]);
    }

    /**
     * Handle the BorderArea "deleted" event.
     *
     * บันทึก log เมื่อมีการลบข้อมูล (รวม soft delete)
     */
    public function deleted(BorderArea $borderArea): void
    {
        Log::warning('BorderArea deleted', [
            'border_area_id' => $borderArea->id,
            'code' => $borderArea->code,
            'name' => $borderArea->name,
            'special_score' => $borderArea->special_score,
            'deleted_by' => Auth::id(),
            'deleted_at' => now()->toDateTimeString(),
        ]);
    }
}

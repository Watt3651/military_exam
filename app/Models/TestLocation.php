<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * TestLocation Model — สถานที่สอบ
 *
 * Section 5.2.5
 *
 * code (1 หลัก) ใช้เป็นหลักที่ 2 ของหมายเลขสอบ 5 หลัก (XYZNN)
 * capacity = จำนวนที่นั่งสอบสูงสุดของสถานที่นั้น
 *
 * @property int $id
 * @property string $name ชื่อสถานที่สอบ
 * @property string $code รหัสสถานที่ 1 หลัก (1-9)
 * @property string|null $address ที่อยู่
 * @property int $capacity จำนวนที่นั่งสอบ
 * @property bool $is_active สถานะใช้งาน
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExamRegistration> $examRegistrations
 */
class TestLocation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'capacity',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration (Spatie)
    |--------------------------------------------------------------------------
    */

    /**
     * กำหนด fields ที่ต้องการ log เมื่อมีการเปลี่ยนแปลง
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'address', 'capacity', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * การลงทะเบียนสอบที่ใช้สถานที่นี้
     * exam_registrations.test_location_id → test_locations.id
     */
    public function examRegistrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * เฉพาะสถานที่ที่ active
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * เรียงตาม code
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('code');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * ตรวจสอบว่ายังรับผู้สอบได้อีกหรือไม่
     * โดยเทียบจำนวนผู้สมัครปัจจุบันกับ capacity
     */
    public function hasAvailableCapacity(?int $examSessionId = null): bool
    {
        if ($this->capacity <= 0) {
            return true; // ไม่จำกัดจำนวน
        }

        $query = $this->examRegistrations()
            ->where('status', '!=', 'cancelled');

        if ($examSessionId) {
            $query->where('exam_session_id', $examSessionId);
        }

        return $query->count() < $this->capacity;
    }
}

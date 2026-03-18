<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PositionQuota Model — อัตรา (โควตา) ตำแหน่งต่อรอบสอบ
 *
 * Section 5.2.10
 *
 * เช่น รอบสอบจ่าเอก ปี 2569:
 *   - ตำแหน่ง "ผบ.หมู่" โควตา 50 คน
 *   - ตำแหน่ง "ผช.หน.ชุด" โควตา 30 คน
 *
 * @property int $id
 * @property int $exam_session_id FK exam_sessions
 * @property string $exam_level ระดับที่สอบ (sergeant_major, master_sergeant)
 * @property string $position_name ชื่อตำแหน่ง
 * @property int $quota_count จำนวนอัตราที่เปิด
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read int $registered_count จำนวนผู้สมัครในตำแหน่งนี้
 * @property-read int $remaining_count จำนวนอัตราที่เหลือ
 * @property-read ExamSession $examSession
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExamRegistration> $examRegistrations
 */
class PositionQuota extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_session_id',
        'exam_level',
        'position_name',
        'quota_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quota_count' => 'integer',
        ];
    }

    /**
     * จำนวนอัตราที่เหลือ (null = ไม่จำกัด)
     */
    public function getRemainingCountAttribute(): ?int
    {
        if ($this->quota_count === null) {
            return null; // ไม่จำกัดจำนวน
        }
        return max(0, $this->quota_count - $this->registered_count);
    }

    /**
     * ตรวจสอบว่ายังมีอัตราเหลือหรือไม่ (null = ไม่จำกัด)
     */
    public function hasAvailableQuota(): bool
    {
        return $this->remaining_count === null || $this->remaining_count > 0;
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
            ->logOnly(['exam_session_id', 'position_name', 'quota_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * รอบสอบที่โควตานี้สังกัด
     * position_quotas.exam_session_id → exam_sessions.id
     */
    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * การลงทะเบียนที่เลือกตำแหน่งนี้
     * exam_registrations.position_quota_id → position_quotas.id
     */
    public function examRegistrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * จำนวนผู้สมัครที่เลือกตำแหน่งนี้ (ไม่นับ cancelled)
     */
    public function getRegisteredCountAttribute(): int
    {
        return $this->examRegistrations()
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * กรองตามรอบสอบ
     */
    public function scopeBySession(Builder $query, int $examSessionId): Builder
    {
        return $query->where('exam_session_id', $examSessionId);
    }
}

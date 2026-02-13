<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * ExamRegistration Model — การลงทะเบียนสอบ
 *
 * Section 5.2.4
 *
 * เชื่อม examinee ↔ exam_session (Many-to-Many through pivot)
 * unique(examinee_id, exam_session_id) → 1 คน สมัครได้ 1 รอบเท่านั้น
 *
 * หมายเลขสอบ 5 หลัก (XYZNN):
 *   X  = branch.code (เหล่า)
 *   Y  = test_location.code (สถานที่สอบ)
 *   Z  = exam_level digit (1=จ่าเอก, 2=พันจ่าเอก)
 *   NN = ลำดับ (01-99)
 *
 * Status flow: pending → confirmed → (cancelled)
 *
 * @property int $id
 * @property int $examinee_id FK examinees
 * @property int $exam_session_id FK exam_sessions
 * @property string|null $exam_number หมายเลขสอบ 5 หลัก
 * @property int $test_location_id FK test_locations
 * @property int|null $position_quota_id FK position_quotas
 * @property string $status pending|confirmed|cancelled
 * @property \Illuminate\Support\Carbon $registered_at วันเวลาที่สมัคร
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Examinee $examinee
 * @property-read ExamSession $examSession
 * @property-read TestLocation $testLocation
 * @property-read PositionQuota|null $positionQuota
 */
class ExamRegistration extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'examinee_id',
        'exam_session_id',
        'exam_number',
        'test_location_id',
        'position_quota_id',
        'status',
        'registered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    /** @var string รอดำเนินการ */
    public const STATUS_PENDING = 'pending';

    /** @var string ยืนยันแล้ว */
    public const STATUS_CONFIRMED = 'confirmed';

    /** @var string ยกเลิก */
    public const STATUS_CANCELLED = 'cancelled';

    /** @var array<string, string> ชื่อสถานะภาษาไทย */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'รอดำเนินการ',
        self::STATUS_CONFIRMED => 'ยืนยันแล้ว',
        self::STATUS_CANCELLED => 'ยกเลิก',
    ];

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
            ->logOnly([
                'examinee_id',
                'exam_session_id',
                'exam_number',
                'test_location_id',
                'position_quota_id',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * ผู้เข้าสอบ
     * exam_registrations.examinee_id → examinees.id
     */
    public function examinee(): BelongsTo
    {
        return $this->belongsTo(Examinee::class);
    }

    /**
     * รอบสอบ
     * exam_registrations.exam_session_id → exam_sessions.id
     */
    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * สถานที่สอบ
     * exam_registrations.test_location_id → test_locations.id
     */
    public function testLocation(): BelongsTo
    {
        return $this->belongsTo(TestLocation::class);
    }

    /**
     * อัตราตำแหน่ง (nullable)
     * exam_registrations.position_quota_id → position_quotas.id
     */
    public function positionQuota(): BelongsTo
    {
        return $this->belongsTo(PositionQuota::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * ชื่อสถานะภาษาไทย
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
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

    /**
     * กรองตามสถานะ
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * เฉพาะที่ยังไม่ถูกยกเลิก
     */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * กรองตามสถานที่สอบ
     */
    public function scopeByLocation(Builder $query, int $testLocationId): Builder
    {
        return $query->where('test_location_id', $testLocationId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /** ตรวจสอบว่ายังรอดำเนินการ */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** ตรวจสอบว่ายืนยันแล้ว */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /** ตรวจสอบว่ายกเลิกแล้ว */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}

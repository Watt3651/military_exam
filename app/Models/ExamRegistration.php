<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
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
 * exam_number เป็นคนละแกนข้อมูล ใช้บอกว่ามีหมายเลขสอบแล้วหรือยัง
 *
 * @property int $id
 * @property int $examinee_id FK examinees
 * @property int $exam_session_id FK exam_sessions
 * @property string|null $exam_number หมายเลขสอบ 5 หลัก
 * @property int $test_location_id FK test_locations
 * @property int|null $position_quota_id FK position_quotas
 * @property string|null $exam_position ตำแหน่งที่สมัครสอบ
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
        'exam_level',
        'exam_number',
        'test_location_id',
        'position_quota_id',
        'exam_position',
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
            // 'exam_position' => 'array', // ลบออกเพื่อป้องกันการ encode ซ้ำ
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    /** @var string รอการยืนยันการสมัคร */
    public const STATUS_PENDING = 'pending';

    /** @var string ยืนยันการสมัครแล้ว */
    public const STATUS_CONFIRMED = 'confirmed';

    /** @var string ยกเลิก */
    public const STATUS_CANCELLED = 'cancelled';

    /** @var array<string, string> ชื่อสถานะภาษาไทย */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'รอยืนยันการสมัคร',
        self::STATUS_CONFIRMED => 'ยืนยันการสมัครแล้ว',
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

    /**
     * ตำแหน่งที่สมัครสอบ (แสดงเป็น string สำหรับ display)
     */
    public function getExamPositionDisplayAttribute(): string
    {
        try {
            // ตอนนี้ exam_position เป็น string ธรรมดา ไม่ใช่ array
            $value = $this->attributes['exam_position'] ?? '';
            
            // ถ้าเป็น array (กรณีพิเศษ) ให้แปลงเป็น string
            if (is_array($value)) {
                return implode(', ', $value);
            }
            
            // ถ้าเป็น JSON string ให้ decode
            if (is_string($value) && str_contains($value, '[') && str_contains($value, ']')) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return implode(', ', $decoded);
                }
            }
            
            // ถ้าเป็น string ธรรมดาที่มี comma
            if (is_string($value) && str_contains($value, ',')) {
                return $value;
            }
            
            // ถ้าเป็น string ธรรมดาเดี่ยวๆ
            return (string) $value ?: $this->positionQuota?->position_name ?? '-';
            
        } catch (\Exception $e) {
            // กรณี emergency ให้คืนค่าว่าง
            return '-';
        }
    }

    /**
     * ตำแหน่งที่สมัครสอบ (ดึงจาก position_quota ถ้ามี)
     */
    public function getExamPositionAttribute(): mixed
    {
        $value = $this->attributes['exam_position'] ?? '';
        
        // ถ้าเป็น JSON string ให้ decode เป็น array
        if (str_contains($value, '[') && str_contains($value, ']')) {
            $decoded = json_decode($value, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $value;
        }
        
        // ถ้าเป็น string ที่มี comma ให้แปลงเป็น array
        if (str_contains($value, ',')) {
            return array_map('trim', explode(',', $value));
        }
        
        return $value;
    }

    /**
     * จำนวนตำแหน่งที่เลือก
     */
    public function getPositionCountAttribute(): int
    {
        // ตรวจสอบว่าเป็น JSON string หรือ array
        $positions = $this->exam_position;
        
        // ถ้าเป็น string ให้ decode JSON
        if (is_string($positions)) {
            $decoded = json_decode($positions, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $positions = $decoded;
            }
        }
        
        if (is_array($positions)) {
            return count($positions);
        }
        
        return $positions ? 1 : 0;
    }

    /**
     * ตำแหน่งแรกที่เลือก (ลำดับความสำคัญสูงสุด)
     */
    public function getFirstPositionAttribute(): ?string
    {
        // ตรวจสอบว่าเป็น JSON string หรือ array
        $positions = $this->exam_position;
        
        // ถ้าเป็น string ให้ decode JSON
        if (is_string($positions)) {
            $decoded = json_decode($positions, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $positions = $decoded;
            }
        }
        
        if (is_array($positions) && !empty($positions)) {
            return $positions[0];
        }
        
        return $positions ?? $this->positionQuota?->position_name ?? null;
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

    /** ตรวจสอบว่ายังรอยืนยันการสมัคร */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /** ตรวจสอบว่ายืนยันการสมัครแล้ว */
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

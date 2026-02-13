<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * ExamSession Model — รอบสอบ
 *
 * Section 5.2.3
 *
 * 1 ปี มี 2 ระดับ: sergeant_major (จ่าเอก) / master_sergeant (พันจ่าเอก)
 * unique(year, exam_level) → 1 ปี + 1 ระดับ = 1 รอบสอบเท่านั้น
 *
 * @property int $id
 * @property int $year ปีการสอบ (พ.ศ.)
 * @property string $exam_level sergeant_major|master_sergeant
 * @property \Illuminate\Support\Carbon $registration_start วันเริ่มรับสมัคร
 * @property \Illuminate\Support\Carbon $registration_end วันปิดรับสมัคร
 * @property \Illuminate\Support\Carbon $exam_date วันสอบ
 * @property bool $is_active เปิดใช้งาน
 * @property bool $is_archived ถูก archive แล้ว
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read string $exam_level_label ชื่อระดับภาษาไทย
 * @property-read bool $is_registration_open อยู่ในช่วงรับสมัครหรือไม่
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExamRegistration> $examRegistrations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PositionQuota> $positionQuotas
 */
class ExamSession extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'year',
        'exam_level',
        'registration_start',
        'registration_end',
        'exam_date',
        'is_active',
        'is_archived',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'registration_start' => 'date',
            'registration_end' => 'date',
            'exam_date' => 'date',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    /** @var string จ่าเอก */
    public const LEVEL_SERGEANT_MAJOR = 'sergeant_major';

    /** @var string พันจ่าเอก */
    public const LEVEL_MASTER_SERGEANT = 'master_sergeant';

    /** @var array<string, string> ชื่อระดับภาษาไทย */
    public const LEVEL_LABELS = [
        self::LEVEL_SERGEANT_MAJOR => 'จ่าเอก',
        self::LEVEL_MASTER_SERGEANT => 'พันจ่าเอก',
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
                'year',
                'exam_level',
                'registration_start',
                'registration_end',
                'exam_date',
                'is_active',
                'is_archived',
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
     * การลงทะเบียนสอบในรอบนี้
     * exam_registrations.exam_session_id → exam_sessions.id
     */
    public function examRegistrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }

    /**
     * อัตราตำแหน่ง (โควตา) ของรอบสอบนี้
     * position_quotas.exam_session_id → exam_sessions.id
     */
    public function positionQuotas(): HasMany
    {
        return $this->hasMany(PositionQuota::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * ชื่อระดับภาษาไทย เช่น "จ่าเอก"
     */
    public function getExamLevelLabelAttribute(): string
    {
        return self::LEVEL_LABELS[$this->exam_level] ?? $this->exam_level;
    }

    /**
     * ตรวจสอบว่าอยู่ในช่วงเปิดรับสมัครหรือไม่
     */
    public function getIsRegistrationOpenAttribute(): bool
    {
        if (! $this->is_active || $this->is_archived) {
            return false;
        }

        $today = Carbon::today();

        return $today->between($this->registration_start, $this->registration_end);
    }

    /**
     * ชื่อแสดงผล เช่น "รอบสอบจ่าเอก ปี 2569"
     */
    public function getDisplayNameAttribute(): string
    {
        return "รอบสอบ{$this->exam_level_label} ปี {$this->year}";
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * เฉพาะรอบสอบที่ active
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_archived', false);
    }

    /**
     * กรองตามปี
     */
    public function scopeByYear(Builder $query, int $year): Builder
    {
        return $query->where('year', $year);
    }

    /**
     * กรองตามระดับ
     */
    public function scopeByLevel(Builder $query, string $level): Builder
    {
        return $query->where('exam_level', $level);
    }

    /**
     * เฉพาะรอบที่กำลังเปิดรับสมัคร
     */
    public function scopeRegistrationOpen(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query->where('is_active', true)
            ->where('is_archived', false)
            ->where('registration_start', '<=', $today)
            ->where('registration_end', '>=', $today);
    }
}

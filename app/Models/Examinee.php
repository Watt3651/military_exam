<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Examinee Model — ข้อมูลผู้เข้าสอบ
 *
 * Section 5.2.2
 *
 * ขยายจาก users (1:1) — เก็บข้อมูลเฉพาะผู้เข้าสอบ เช่น ตำแหน่ง, เหล่า, อายุ
 *
 * สูตรคำนวณคะแนน:
 *   คะแนนค้างบรรจุ = (ปีปัจจุบัน - eligible_year) - suspended_years
 *   คะแนนพิเศษ     = border_areas.special_score
 *   คะแนนรวม       = pending_score + special_score
 *
 * @property int $id
 * @property int $user_id FK users (1:1)
 * @property string $position ตำแหน่ง
 * @property int $branch_id FK branches — เหล่า
 * @property int $age อายุ
 * @property int $eligible_year ปีที่มีสิทธิ์สอบ (พ.ศ.)
 * @property int $suspended_years ปีที่ถูกงดบำเหน็จ
 * @property float $pending_score คะแนนค้างบรรจุ (auto-calculated)
 * @property float $special_score คะแนนพิเศษ (จาก border_area)
 * @property int|null $border_area_id FK border_areas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read float $total_score คะแนนรวม
 * @property-read User $user
 * @property-read Branch $branch
 * @property-read BorderArea|null $borderArea
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExamRegistration> $examRegistrations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExamineeEditLog> $editLogs
 */
class Examinee extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'position',
        'branch_id',
        'age',
        'eligible_year',
        'suspended_years',
        'pending_score',
        'special_score',
        'border_area_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'eligible_year' => 'integer',
            'suspended_years' => 'integer',
            'pending_score' => 'decimal:2',
            'special_score' => 'decimal:2',
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
            ->logOnly([
                'position',
                'branch_id',
                'age',
                'eligible_year',
                'suspended_years',
                'pending_score',
                'special_score',
                'border_area_id',
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
     * ข้อมูลผู้ใช้ (1:1 inverse)
     * examinees.user_id → users.id
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * เหล่าทหาร
     * examinees.branch_id → branches.id
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * พื้นที่ชายแดน (nullable)
     * examinees.border_area_id → border_areas.id
     */
    public function borderArea(): BelongsTo
    {
        return $this->belongsTo(BorderArea::class);
    }

    /**
     * การลงทะเบียนสอบทั้งหมด
     * exam_registrations.examinee_id → examinees.id
     */
    public function examRegistrations(): HasMany
    {
        return $this->hasMany(ExamRegistration::class);
    }

    /**
     * ประวัติการแก้ไขข้อมูลโดย Staff
     * examinee_edit_logs.examinee_id → examinees.id
     */
    public function editLogs(): HasMany
    {
        return $this->hasMany(ExamineeEditLog::class)->orderByDesc('edited_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * คะแนนรวม = คะแนนค้างบรรจุ + คะแนนพิเศษ
     */
    public function getTotalScoreAttribute(): float
    {
        return (float) $this->pending_score + (float) $this->special_score;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * กรองตามเหล่า
     */
    public function scopeByBranch(Builder $query, int $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * กรองตามปีที่มีสิทธิ์สอบ
     */
    public function scopeByEligibleYear(Builder $query, int $year): Builder
    {
        return $query->where('eligible_year', $year);
    }

    /**
     * กรองเฉพาะผู้ที่อยู่ในพื้นที่ชายแดน
     */
    public function scopeInBorderArea(Builder $query): Builder
    {
        return $query->whereNotNull('border_area_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * คำนวณคะแนนค้างบรรจุ
     * สูตร: (ปีปัจจุบัน - ปีที่มีสิทธิ์สอบ) - ปีที่ถูกงดบำเหน็จ
     */
    public function calculatePendingScore(?int $currentYear = null): float
    {
        $currentYear = $currentYear ?? (int) date('Y') + 543; // พ.ศ.

        return max(0, ($currentYear - $this->eligible_year) - $this->suspended_years);
    }

    /**
     * ดึงคะแนนพิเศษจาก border_area
     */
    public function syncSpecialScore(): float
    {
        if ($this->border_area_id && $this->borderArea) {
            return (float) $this->borderArea->special_score;
        }

        return 0.00;
    }

    /**
     * คำนวณและบันทึกคะแนนทั้งหมด
     */
    public function recalculateScores(?int $currentYear = null): void
    {
        $this->pending_score = $this->calculatePendingScore($currentYear);
        $this->special_score = $this->syncSpecialScore();
        $this->save();
    }
}

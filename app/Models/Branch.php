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
 * Branch Model — เหล่าทหาร
 *
 * Section 5.2.6
 *
 * เช่น ทหารราบ, ทหารปืนใหญ่, ทหารม้า, ทหารช่าง, ทหารสื่อสาร, etc.
 * code (1 หลัก) ใช้เป็นหลักแรกของหมายเลขสอบ 5 หลัก (XYZNN)
 *
 * @property int $id
 * @property string $name ชื่อเหล่า
 * @property string $code รหัสเหล่า 1 หลัก (1-9)
 * @property bool $is_active สถานะใช้งาน
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Examinee> $examinees
 */
class Branch extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
            ->logOnly(['name', 'code', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * ผู้เข้าสอบที่อยู่ในเหล่านี้
     * examinees.branch_id → branches.id
     */
    public function examinees(): HasMany
    {
        return $this->hasMany(Examinee::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * เฉพาะเหล่าที่ active
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
}

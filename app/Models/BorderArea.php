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
 * BorderArea Model — พื้นที่ชายแดน 🔥
 *
 * Section 5.2.7
 *
 * ใช้กำหนดคะแนนพิเศษ (special_score) ให้ผู้เข้าสอบที่ประจำอยู่ในพื้นที่ชายแดน
 * เช่น จ.นราธิวาส, จ.ปัตตานี, จ.ยะลา — ผู้ที่อยู่ในพื้นที่เหล่านี้จะได้คะแนนพิเศษเพิ่ม
 *
 * ทุกครั้งที่เปลี่ยน special_score จะบันทึกลง border_area_score_history
 *
 * @property int $id
 * @property string $code รหัสพื้นที่ เช่น BA01, BA02
 * @property string $name ชื่อพื้นที่ เช่น จ.นราธิวาส
 * @property float $special_score คะแนนพิเศษ
 * @property string|null $description รายละเอียดเพิ่มเติม
 * @property bool $is_active สถานะใช้งาน
 * @property int|null $created_by ผู้สร้าง (FK users)
 * @property int|null $updated_by ผู้แก้ไขล่าสุด (FK users)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read User|null $createdByUser
 * @property-read User|null $updatedByUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Examinee> $examinees
 * @property-read \Illuminate\Database\Eloquent\Collection<int, BorderAreaScoreHistory> $scoreHistory
 */
class BorderArea extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'special_score',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'special_score' => 'decimal:2',
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
            ->logOnly(['code', 'name', 'special_score', 'description', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * ผู้สร้างพื้นที่ชายแดน
     * border_areas.created_by → users.id
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * ผู้แก้ไขพื้นที่ชายแดนล่าสุด
     * border_areas.updated_by → users.id
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * ผู้เข้าสอบที่อยู่ในพื้นที่ชายแดนนี้
     * examinees.border_area_id → border_areas.id
     */
    public function examinees(): HasMany
    {
        return $this->hasMany(Examinee::class);
    }

    /**
     * ประวัติการเปลี่ยนแปลงคะแนนพิเศษ
     * border_area_score_history.border_area_id → border_areas.id
     */
    public function scoreHistory(): HasMany
    {
        return $this->hasMany(BorderAreaScoreHistory::class)->orderByDesc('changed_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * เฉพาะพื้นที่ที่ active
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
     * แสดงรหัสและชื่อ เช่น "BA01 - จ.นราธิวาส"
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * แสดงคะแนนพร้อมชื่อ เช่น "จ.นราธิวาส (+5.00)"
     */
    public function getNameWithScoreAttribute(): string
    {
        return "{$this->name} (+{$this->special_score})";
    }
}

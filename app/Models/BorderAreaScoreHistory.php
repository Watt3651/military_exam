<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BorderAreaScoreHistory Model — ประวัติการเปลี่ยนแปลงคะแนนพิเศษพื้นที่ชายแดน 🔥
 *
 * Section 5.2.8
 *
 * บันทึกทุกครั้งที่ Staff เปลี่ยน special_score ของ border_area
 * old_score = NULL หมายถึงสร้างพื้นที่ใหม่ (ยังไม่เคยมีคะแนน)
 *
 * ตารางนี้เป็น append-only — ไม่มีการแก้ไขหรือลบ record
 *
 * @property int $id
 * @property int $border_area_id FK border_areas
 * @property float|null $old_score คะแนนเดิม (NULL = สร้างใหม่)
 * @property float $new_score คะแนนใหม่
 * @property int $changed_by FK users — เจ้าหน้าที่ที่เปลี่ยน
 * @property string|null $reason เหตุผลที่เปลี่ยน
 * @property \Illuminate\Support\Carbon $changed_at วันเวลาที่เปลี่ยน
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @property-read string $change_summary สรุปการเปลี่ยนแปลง
 * @property-read BorderArea $borderArea
 * @property-read User $changedBy
 */
class BorderAreaScoreHistory extends Model
{
    use HasFactory;

    /**
     * ตารางจริงในระบบ (ตั้งชื่อ singular ตาม migration)
     *
     * @var string
     */
    protected $table = 'border_area_score_history';

    /**
     * ตาราง audit — ไม่ต้องการ updated_at
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'border_area_id',
        'old_score',
        'new_score',
        'changed_by',
        'reason',
        'changed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_score' => 'decimal:2',
            'new_score' => 'decimal:2',
            'changed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * พื้นที่ชายแดนที่ถูกเปลี่ยนคะแนน
     * border_area_score_history.border_area_id → border_areas.id
     */
    public function borderArea(): BelongsTo
    {
        return $this->belongsTo(BorderArea::class);
    }

    /**
     * เจ้าหน้าที่ที่เปลี่ยนคะแนน
     * border_area_score_history.changed_by → users.id
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * สรุปการเปลี่ยนแปลง เช่น "0.00 → 5.00" หรือ "ใหม่ → 5.00"
     */
    public function getChangeSummaryAttribute(): string
    {
        $old = $this->old_score !== null
            ? number_format((float) $this->old_score, 2)
            : 'ใหม่';

        return "{$old} → " . number_format((float) $this->new_score, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * กรองตามพื้นที่ชายแดน
     */
    public function scopeByBorderArea(Builder $query, int $borderAreaId): Builder
    {
        return $query->where('border_area_id', $borderAreaId);
    }

    /**
     * เรียงจากล่าสุดก่อน
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('changed_at');
    }
}

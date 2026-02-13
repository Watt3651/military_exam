<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExamineeEditLog Model — บันทึกการแก้ไขข้อมูลผู้เข้าสอบโดย Staff 🔥
 *
 * Section 5.2.9
 *
 * เก็บทุกครั้งที่ Staff แก้ไขข้อมูลผู้เข้าสอบ
 * field_name: ชื่อ field ที่แก้ เช่น 'rank', 'first_name', 'branch_id'
 * reason: เหตุผลที่แก้ (required จาก UI)
 *
 * ตารางนี้เป็น append-only — ไม่มีการแก้ไขหรือลบ record
 *
 * @property int $id
 * @property int $examinee_id FK examinees — ผู้เข้าสอบที่ถูกแก้ไข
 * @property int $edited_by FK users — เจ้าหน้าที่ที่แก้ไข
 * @property string $field_name ชื่อ field ที่แก้ไข
 * @property string|null $old_value ค่าเดิม
 * @property string|null $new_value ค่าใหม่
 * @property string|null $reason เหตุผลในการแก้ไข
 * @property \Illuminate\Support\Carbon $edited_at วันเวลาที่แก้ไข
 * @property \Illuminate\Support\Carbon $created_at
 *
 * @property-read string $field_label ชื่อ field ภาษาไทย
 * @property-read string $change_summary สรุปการเปลี่ยนแปลง
 * @property-read Examinee $examinee
 * @property-read User $editedBy
 */
class ExamineeEditLog extends Model
{
    use HasFactory;

    /**
     * ตาราง audit — ไม่ต้องการ updated_at
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'examinee_id',
        'edited_by',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'edited_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Constants — แปลชื่อ field เป็นภาษาไทย
    |--------------------------------------------------------------------------
    */

    /** @var array<string, string> */
    public const FIELD_LABELS = [
        'rank' => 'ยศ',
        'first_name' => 'ชื่อ',
        'last_name' => 'นามสกุล',
        'position' => 'ตำแหน่ง',
        'branch_id' => 'เหล่า',
        'age' => 'อายุ',
        'eligible_year' => 'ปีที่มีสิทธิ์สอบ',
        'suspended_years' => 'ปีที่ถูกงดบำเหน็จ',
        'border_area_id' => 'พื้นที่ชายแดน',
        'test_location_id' => 'สถานที่สอบ',
        'special_score' => 'คะแนนพิเศษ',
        'pending_score' => 'คะแนนค้างบรรจุ',
        'status' => 'สถานะ',
        'exam_number' => 'หมายเลขสอบ',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * ผู้เข้าสอบที่ถูกแก้ไข
     * examinee_edit_logs.examinee_id → examinees.id
     */
    public function examinee(): BelongsTo
    {
        return $this->belongsTo(Examinee::class);
    }

    /**
     * เจ้าหน้าที่ที่แก้ไข
     * examinee_edit_logs.edited_by → users.id
     */
    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * ชื่อ field ภาษาไทย เช่น 'rank' → 'ยศ'
     */
    public function getFieldLabelAttribute(): string
    {
        return self::FIELD_LABELS[$this->field_name] ?? $this->field_name;
    }

    /**
     * สรุปการเปลี่ยนแปลง เช่น "ยศ: ส.อ. → จ.ส.อ."
     */
    public function getChangeSummaryAttribute(): string
    {
        $old = $this->old_value ?? '(ว่าง)';
        $new = $this->new_value ?? '(ว่าง)';

        return "{$this->field_label}: {$old} → {$new}";
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * กรองตามผู้เข้าสอบ
     */
    public function scopeByExaminee(Builder $query, int $examineeId): Builder
    {
        return $query->where('examinee_id', $examineeId);
    }

    /**
     * กรองตาม field ที่แก้ไข
     */
    public function scopeByField(Builder $query, string $fieldName): Builder
    {
        return $query->where('field_name', $fieldName);
    }

    /**
     * เรียงจากล่าสุดก่อน
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('edited_at');
    }
}

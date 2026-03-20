<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data Review Log Model
 * 
 * บันทึกการแจ้งเตือนตรวจสอบข้อมูลผู้สมัคร
 * 
 * @property int $id
 * @property int $examinee_id
 * @property int $staff_id
 * @property string $message
 * @property string $status pending|reviewed|ignored
 * @property \Carbon\Carbon|null $reviewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read Examinee $examinee
 * @property-read User $staff
 */
class DataReviewLog extends Model
{
    protected $fillable = [
        'examinee_id',
        'staff_id', 
        'message',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    /**
     * ผู้สมัครที่ถูกแจ้งเตือน
     */
    public function examinee(): BelongsTo
    {
        return $this->belongsTo(Examinee::class);
    }

    /**
     * Staff ที่ส่งแจ้งเตือน
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * เฉพาะที่รอการตรวจสอบ
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * เฉพาะที่ตรวจสอบแล้ว
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    /**
     * เฉพาะที่ถูก ignore
     */
    public function scopeIgnored($query)
    {
        return $query->where('status', 'ignored');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model — ผู้ใช้งานระบบสอบเลื่อนฐานะนายทหารประทวน
 *
 * Section 5.2.1
 *
 * @property int $id
 * @property string $national_id หมายเลขประจำตัว 13 หลัก
 * @property string $rank ยศ
 * @property string $first_name ชื่อ
 * @property string $last_name นามสกุล
 * @property string|null $email อีเมล (Staff/Commander)
 * @property string $password bcrypt hash
 * @property string $role examinee|staff|commander|password_support
 * @property bool $is_active สถานะใช้งาน
 * @property bool $must_change_password บังคับเปลี่ยนรหัสผ่านหลัง login
 * @property int|null $created_by ผู้สร้างบัญชี (FK users.id)
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read string $full_name ยศ + ชื่อ + นามสกุล
 * @property-read string $short_name ชื่อ + นามสกุล
 * @property-read Examinee|null $examinee
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $createdUsers
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory,
        HasRoles,
        LogsActivity,
        Notifiable,
        SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'national_id',
        'rank',
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function username(): string
    {
        return 'national_id';
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
                'national_id',
                'rank',
                'first_name',
                'last_name',
                'email',
                'role',
                'is_active',
                'must_change_password',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * ชื่อเต็ม: ยศ + ชื่อ + นามสกุล
     * เช่น "พ.อ. สมชาย ใจดี"
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->rank} {$this->first_name} {$this->last_name}";
    }

    /**
     * ชื่อย่อ: ชื่อ + นามสกุล (ไม่มียศ)
     * เช่น "สมชาย ใจดี"
     */
    public function getShortNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * ผู้สร้างบัญชี (self-referencing)
     * Staff สร้าง Staff/Commander accounts
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * บัญชีที่สร้างโดยผู้ใช้นี้
     */
    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }

    /**
     * ข้อมูลผู้เข้าสอบ (1:1)
     * เฉพาะ role = examinee
     */
    public function examinee(): HasOne
    {
        return $this->hasOne(Examinee::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * เฉพาะผู้ใช้ที่ active
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * กรองตาม role
     */
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * ตรวจสอบว่าเป็น role ที่ระบุหรือไม่
     */
    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    /** แปลง role เป็นชื่อภาษาไทย */
    public static function roleLabel(?string $role): string
    {
        return match ($role) {
            'staff' => 'เจ้าหน้าที่',
            'commander' => 'ผู้บังคับบัญชา',
            'password_support' => 'เจ้าหน้าที่ช่วยรีเซ็ตรหัสผ่าน',
            'examinee' => 'ผู้สมัครสอบ',
            default => 'ไม่ระบุ',
        };
    }

    /** แปลง role เป็นชุดสี badge */
    public static function roleBadgeClasses(?string $role): string
    {
        return match ($role) {
            'staff' => 'bg-blue-100 text-blue-800',
            'commander' => 'bg-purple-100 text-purple-800',
            'password_support' => 'bg-amber-100 text-amber-800',
            'examinee' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /** ชื่อบทบาทภาษาไทยของผู้ใช้ปัจจุบัน */
    public function getRoleLabelAttribute(): string
    {
        return self::roleLabel($this->role);
    }

    /** ชุดสี badge ของบทบาทปัจจุบัน */
    public function getRoleBadgeClassesAttribute(): string
    {
        return self::roleBadgeClasses($this->role);
    }

    /** ตรวจสอบว่าเป็นผู้เข้าสอบ */
    public function isExaminee(): bool
    {
        return $this->isRole('examinee');
    }

    /** ตรวจสอบว่าเป็นเจ้าหน้าที่ */
    public function isStaff(): bool
    {
        return $this->isRole('staff');
    }

    /** ตรวจสอบว่าเป็นเจ้าหน้าที่ช่วยรีเซ็ตรหัสผ่าน */
    public function isPasswordSupport(): bool
    {
        return $this->isRole('password_support');
    }

    /** ตรวจสอบว่าสามารถเข้าหน้าช่วยรีเซ็ตรหัสผ่านได้ */
    public function canSupportPasswords(): bool
    {
        return $this->isStaff() || $this->isPasswordSupport();
    }

    /** ตรวจสอบว่าเป็นผู้บังคับบัญชา */
    public function isCommander(): bool
    {
        return $this->isRole('commander');
    }
}

# Military Promotion Exam System

## 1. ภาพรวมโปรเจกต์

Military Promotion Exam System คือระบบเว็บสำหรับจัดการงานสอบเลื่อนฐานะของกำลังพลชั้นประทวน ตั้งแต่การลงทะเบียนสอบไปจนถึงการสรุปรายงานและการดูข้อมูลเชิงบริหาร

ระบบรองรับกระบวนการหลักครบทั้งวงจร ได้แก่:
- ลงทะเบียนผู้เข้าสอบ
- คำนวณคะแนน
- จัดการข้อมูลหลัก
- ออกเลขสอบอัตโนมัติ
- แดชบอร์ดสถิติ
- ส่งออกรายงาน PDF/Excel
- สำรองข้อมูลตามรอบเวลา

บทบาทผู้ใช้งานหลัก:
- `examinee`: ผู้เข้าสอบ (จัดการโปรไฟล์, ลงทะเบียนสอบ, ติดตามสถานะ, ดาวน์โหลดบัตรสอบ)
- `staff`: เจ้าหน้าที่ (จัดการข้อมูลระบบ, ออกเลขสอบ, ดูรายงาน, จัดการผู้ใช้งาน)
- `commander`: ผู้บังคับบัญชา (ดูแดชบอร์ดแบบอ่านอย่างเดียวและเปรียบเทียบสถิติรายปี)

## 2. ฟีเจอร์หลัก

- ระบบเข้าสู่ระบบด้วย `national_id` และกำหนดสิทธิ์แบบ Role-Based Access Control
- จัดการข้อมูลผู้เข้าสอบและการลงทะเบียนสอบ
- คำนวณคะแนนตามเงื่อนไขระบบ (คะแนนค้างบรรจุ + คะแนนพิเศษ)
- จัดการข้อมูลหลัก:
  - รอบสอบ
  - สถานที่สอบ
  - เหล่า
  - พื้นที่ชายแดนและประวัติการเปลี่ยนคะแนน
  - อัตราตำแหน่ง
- ออกเลขสอบอัตโนมัติรูปแบบ `XYZNN`
- แดชบอร์ดสำหรับ Staff และ Commander พร้อมกราฟ (ApexCharts)
- รายงาน:
  - PDF รายชื่อผู้เข้าสอบ
  - Excel หลายชีต
  - PDF บัตรประจำตัวสอบ
- เก็บประวัติการแก้ไขข้อมูลสำคัญ (Audit Log)
- สำรองข้อมูลและตรวจสุขภาพแบ็กอัตโนมัติ (Spatie Backup)

## 3. เทคโนโลยีที่ใช้ (Tech Stack)

- PHP `^8.2`
- Laravel `^12`
- Livewire `^3` + Volt
- MySQL/MariaDB (แนะนำสำหรับ Production)
- Tailwind CSS + Vite
- ApexCharts
- `barryvdh/laravel-dompdf` (สร้าง PDF)
- `maatwebsite/excel` (ส่งออก Excel)
- `spatie/laravel-permission` (จัดการสิทธิ์)
- `spatie/laravel-activitylog` (บันทึกกิจกรรม)
- `spatie/laravel-backup` (สำรองข้อมูล)
- Pest (ทดสอบระบบ)

## 4. การติดตั้ง (Installation)

1) Clone โปรเจกต์และเข้าโฟลเดอร์

```bash
git clone <your-repo-url> millitary_exam
cd millitary_exam
```

2) ติดตั้ง dependencies

```bash
composer install
npm install
```

3) เตรียมไฟล์ Environment

```bash
cp .env.example .env
php artisan key:generate
```

4) Build assets

```bash
npm run build
```

สำหรับพัฒนาในเครื่อง (Local):

```bash
npm run dev
php artisan serve
```

## 5. การตั้งค่า (Configuration)

กำหนดค่า `.env` อย่างน้อยดังนี้:

- `APP_NAME`, `APP_URL`, `APP_ENV`, `APP_DEBUG`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_*` (Production ควรใช้ SMTP จริง)
- `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`

สำหรับ Production ใช้ค่าเริ่มต้นจาก:

```bash
cp .env.example.production .env
```

จากนั้นแก้ค่าจริงให้ครบก่อน deploy

## 6. การตั้งค่าฐานข้อมูล (Database Setup)

รัน migration:

```bash
php artisan migrate
```

ถ้ามี seeder และต้องการข้อมูลตั้งต้น:

```bash
php artisan db:seed
```

เมื่อตั้งค่าใหม่หรือแก้ `.env` ให้ล้าง cache:

```bash
php artisan config:clear
php artisan cache:clear
```

## 7. การรันเทสต์ (Running Tests)

รันเทสต์ทั้งหมด:

```bash
php artisan test
```

รันเฉพาะ Unit tests:

```bash
php artisan test tests/Unit
```

รันเฉพาะ Feature tests:

```bash
php artisan test tests/Feature
```

## 8. การ Deploy (Deployment)

โปรเจกต์มีสคริปต์ deploy สำหรับ production:

```bash
bash deploy-production.sh
```

ขั้นตอนภายในสคริปต์:
- `composer install --optimize-autoloader --no-dev`
- `php artisan migrate --force`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `php artisan queue:restart`

ตั้งค่า cron สำหรับ scheduler (จำเป็น):

```cron
* * * * * cd /var/www/millitary_exam && php artisan schedule:run >> /dev/null 2>&1
```

## 9. การสำรองและกู้คืนข้อมูล (Backup & Restore)

### 9.1 ตั้งค่า Backup

ไฟล์ config อยู่ที่:
- `config/backup.php`

ตัวแปรที่เกี่ยวข้องใน `.env`:
- `BACKUP_DISK`
- `BACKUP_ARCHIVE_PASSWORD`
- `BACKUP_NOTIFICATION_EMAIL`
- ค่าการเก็บรักษา/monitor:
  - `BACKUP_MAX_AGE_DAYS`
  - `BACKUP_MAX_STORAGE_MB`
  - `BACKUP_KEEP_ALL_DAYS`
  - `BACKUP_KEEP_DAILY_DAYS`
  - `BACKUP_KEEP_WEEKLY_WEEKS`
  - `BACKUP_KEEP_MONTHLY_MONTHS`
  - `BACKUP_KEEP_YEARLY_YEARS`

### 9.2 ตารางเวลาสำรองข้อมูล

- สำรองฐานข้อมูลรายวัน: `backup:run --only-db`
- สำรองเต็มรายสัปดาห์: `backup:run`
- งานดูแลรายวัน: `backup:clean`, `backup:monitor`

### 9.3 คำสั่งสำรองข้อมูลแบบ Manual

```bash
php artisan backup:run --only-db
php artisan backup:run
php artisan backup:list
php artisan backup:clean
php artisan backup:monitor
```

### 9.4 แนวทางกู้คืนข้อมูล (Restore)

1) แตกไฟล์แบ็กอัปจากปลายทางจัดเก็บ  
2) กู้คืนไฟล์ระบบตามที่ต้องการ  
3) กู้คืนฐานข้อมูลจากไฟล์ dump ตัวอย่าง:

```bash
mysql -u <db_user> -p <db_name> < /path/to/database-dump.sql
```

4) สร้าง cache ใหม่หลัง restore:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10. การแก้ปัญหาเบื้องต้น (Troubleshooting)

- **เจอ `Route not defined`**
  - รัน `php artisan route:clear` แล้ว `php artisan route:cache`
- **แก้ `.env` แล้วค่าไม่อัปเดต**
  - รัน `php artisan config:clear`
- **Permission denied ที่ `storage` หรือ `bootstrap/cache`**
  - ปรับ owner/permission ให้ถูกต้องตาม user ของ web server
- **Queue ไม่ทำงาน**
  - ตรวจ worker process และรัน `php artisan queue:restart`
- **Backup ล้มเหลว**
  - ตรวจการเชื่อมต่อ DB, การมีอยู่ของ `mysqldump`, ค่า backup disk, และ mail transport
- **เทสต์ล้มเหลวจาก cache ค้าง**
  - รัน `php artisan config:clear` แล้วทดสอบใหม่

---

สำหรับขั้นตอน production รายละเอียดเพิ่มเติม ดูที่ `PRODUCTION_CHECKLIST.md` และควรอัปเดตให้สอดคล้องกับ README นี้เสมอ

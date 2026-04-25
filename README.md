<<<<<<< HEAD
# rtfrnd
=======
# ระบบรายงานสถานะความพร้อม — กองเรือยุทธการ

เวอร์ชันนี้เปลี่ยนจากการเก็บข้อมูลใน `readiness.json` ไปเป็นฐานข้อมูลผ่าน PDO แล้ว โดยค่าเริ่มต้นใช้ SQLite ไฟล์ `data/readiness.sqlite` สำหรับ local/dev และรองรับ MySQL/MariaDB สำหรับ production พร้อม bootstrap schema, seed users, seed API client และย้ายข้อมูลเดิมจาก `data/readiness.json` ให้โดยอัตโนมัติเมื่อรันครั้งแรก

## สิ่งที่เปลี่ยนจากเวอร์ชันเดิม

- เก็บข้อมูล readiness ในฐานข้อมูลแทน JSON
- ผู้ใช้ย้ายไปอยู่ในตาราง `users`
- มีแนวคิดสิทธิ์พื้นฐานตาม role: `admin`, `officer`, `viewer`, `integration_service`
- รองรับหลายหน่วยผ่านตาราง `units`
- มีหน้า admin สำหรับจัดการผู้ใช้, หน่วย, และ API clients
- มีหน้า admin สำหรับจัดการรายการประเมิน (`readiness_items`)
- มีหน้า audit log viewer สำหรับตรวจสอบการใช้งานย้อนหลัง
- มี endpoint สำหรับรับข้อมูล readiness จากระบบภายนอก
- เก็บ audit log ทุกครั้งที่มีการแก้ข้อมูลหลัก

## 1. ติดตั้ง Apache และ PHP

```bash
sudo apt update
sudo apt install apache2 php php-sqlite3 libapache2-mod-php -y
```

ถ้าจะใช้ MySQL/MariaDB แทน SQLite ให้ติดตั้ง `php-mysql` เพิ่มด้วย

สำหรับ production แนะนำให้ใช้ MySQL/MariaDB เท่านั้น

## 2. วางไฟล์โปรเจกต์

```bash
sudo cp -r readiness-app/ /var/www/html/readiness
```

## 3. กำหนดสิทธิ์โฟลเดอร์ data

```bash
sudo chown -R www-data:www-data /var/www/html/readiness/data
sudo chmod 755 /var/www/html/readiness/data
sudo chmod 644 /var/www/html/readiness/data/readiness.json
```

เมื่อเข้าใช้งานครั้งแรก ระบบจะสร้าง `data/readiness.sqlite` อัตโนมัติ ให้ตรวจสอบว่าผู้ใช้ของเว็บเซิร์ฟเวอร์เขียนไฟล์ในโฟลเดอร์ `data` ได้

## 4. ตั้งค่าฐานข้อมูล

ค่าเริ่มต้นใช้ SQLite โดยไม่ต้องตั้งค่าเพิ่ม แต่สำหรับ production ให้ตั้ง `READINESS_DB_DRIVER=mysql`

ถ้าต้องการใช้ MySQL/MariaDB ให้กำหนด environment variables ก่อนรัน Apache หรือ PHP-FPM:

```bash
export READINESS_APP_ENV=production
export READINESS_DB_DRIVER=mysql
export READINESS_DB_HOST=127.0.0.1
export READINESS_DB_PORT=3306
export READINESS_DB_NAME=readiness
export READINESS_DB_USER=readiness_user
export READINESS_DB_PASS=secret
export READINESS_DB_CONNECT_TIMEOUT=5
export READINESS_API_REQUIRE_CLIENT_KEY=1
export READINESS_API_RATE_LIMIT_PER_MINUTE=60
export READINESS_VISIBLE_ROWS=develop,efficiency,service,result
export READINESS_CALCULATED_ROWS=develop,efficiency,service,result
```

ถ้าใช้ socket หรือ SSL:

```bash
export READINESS_DB_SOCKET=/var/run/mysqld/mysqld.sock
export READINESS_DB_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

ตัวอย่างไฟล์ตั้งค่าดูได้ที่ `production.env.example` และ `.env.ci.example`

ถ้าต้องการเปิด/ปิดการแสดงผลหรือการคำนวณเป็นรายมิติ:

```bash
export READINESS_VISIBLE_ROWS=develop,efficiency,result
export READINESS_CALCULATED_ROWS=develop,efficiency
```

- `READINESS_VISIBLE_ROWS` คือมิติที่จะแสดงในหน้า Dashboard และ Input
- `READINESS_CALCULATED_ROWS` คือมิติที่ใช้คำนวณคะแนนรวมจริง โดยระบบจะ normalize น้ำหนักของมิติที่เลือกให้รวมเป็น 100% อัตโนมัติ
- มิติที่ไม่แสดงจะยังคงอยู่ในฐานข้อมูลและ API ได้ตามปกติ และหน้า Input จะไม่ล้างค่าของมิติที่ซ่อนไว้ตอนกดบันทึก
- ถ้ามีการตั้งค่าผ่านหน้า admin ระบบจะใช้ค่าจากฐานข้อมูลเป็นอันดับแรก และ env จะทำหน้าที่เป็น fallback/default

หมายเหตุ: schema จะถูกสร้างอัตโนมัติเมื่อเชื่อมต่อสำเร็จ

## 5. ผู้ใช้ตั้งต้นและการย้ายข้อมูลเดิม

เมื่อฐานข้อมูลยังว่าง ระบบจะทำสิ่งต่อไปนี้ให้อัตโนมัติ:

1. สร้างหน่วยเริ่มต้น `fleet`
2. seed ผู้ใช้จาก `LEGACY_USERS` ใน `config.php`
3. seed API client เริ่มต้นจาก `DEFAULT_API_CLIENTS`
4. import ข้อมูลจาก `data/readiness.json` เข้า DB

บัญชีเริ่มต้นยังคงเป็นค่าชุดเดิมในโค้ด และควรเปลี่ยนรหัสผ่านทันทีหลังติดตั้งผ่านหน้า admin

## 6. URL หลัก

| URL | หน้า |
|-----|------|
| http://[server-ip]/readiness/ | Dashboard |
| http://[server-ip]/readiness/login.php | เข้าสู่ระบบ |
| http://[server-ip]/readiness/input.php | กรอกข้อมูลตามสิทธิ์ |
| http://[server-ip]/readiness/admin/users.php | จัดการผู้ใช้ |
| http://[server-ip]/readiness/admin/units.php | จัดการหน่วย |
| http://[server-ip]/readiness/admin/readiness-items.php | จัดการรายการประเมิน |
| http://[server-ip]/readiness/admin/readiness-display-settings.php | ตั้งค่าการแสดงผลและการคำนวณรายมิติ |
| http://[server-ip]/readiness/admin/readiness-sync.php | Preview diff / backup / apply sync |
| http://[server-ip]/readiness/admin/api-clients.php | จัดการ API clients |
| http://[server-ip]/readiness/admin/mock-percent-mappings.php | จัดการ Mock API metric mappings |
| http://[server-ip]/readiness/admin/audit-log.php | Audit log viewer |

## 7. การกำหนดสิทธิ์โดยสรุป

- `admin` ดูได้ทุกหน่วย แก้ได้ทุกหน่วย และจัดการผู้ใช้/หน่วย/API clients ได้
- `officer` แก้ข้อมูลได้เฉพาะหน่วยของตัวเอง
- `viewer` ดูข้อมูลได้ แต่ไม่มีสิทธิ์แก้ไข
- `integration_service` สงวนไว้สำหรับระบบภายนอกผ่าน API

## 8. การรับข้อมูลจากระบบภายนอก

Endpoint:

```text
POST /api/v1/ingest.php
```

Auth:

- แนะนำสำหรับ production: `X-Client-Key` + `X-Client-Secret`
- รองรับแบบ fallback: `Authorization: Bearer <secret>` หรือ `X-API-Key: <secret>`
- สำหรับ mock endpoint ใช้ auth แบบเดียวกัน

Payload ตัวอย่าง:

```json
{
    "unit_code": "fleet",
    "rows": {
        "develop": {
            "personnel": [0, 1, 2, 0, 2],
            "material": [2, 0, 0, 2, 2, 0, 2],
            "tactic": [0, 0, 1, 0, 0]
        },
        "efficiency": {
            "personnel": [0, 0],
            "material": [0, 0, 0, 0, 0, 0, 0],
            "tactic": [0, 0, 0, 0]
        },
        "service": {
            "personnel": [0, 0, 0, 0],
            "material": [0, 0, 0, 0, 0, 0, 0],
            "tactic": [0, 0, 0, 0, 0]
        },
        "result": {
            "personnel": [0, 0],
            "material": [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            "tactic": [0, 0, 0, 0, 0]
        }
    }
}
```

ตัวอย่าง `curl`:

```bash
curl -X POST http://[server-ip]/readiness/api/v1/ingest.php \
    -H "X-Client-Key: default-ingestion-client" \
    -H "X-Client-Secret: change-me-in-production" \
    -H "Content-Type: application/json" \
    -d @payload.json
```

หมายเหตุ:

- ถ้า API client ถูกผูกกับหน่วยไว้แล้ว ไม่จำเป็นต้องส่ง `unit_code`
- ค่าความพร้อมต้องอยู่ในช่วง `0-2`
- ปัจจุบันลำดับความสำคัญของข้อมูลคือ `manual` มาก่อน `integration_mock` มาก่อน `api`
- สามารถกำหนด `allowed_ips` และ `rate_limit_per_minute` ต่อ client ได้จากหน้า admin
- secret ของ API client จะถูกแสดงเพียงครั้งเดียวตอนสร้างหรือ rotate

Mock endpoint สำหรับทดสอบรับ metric แบบ partial (ไม่ต้องส่งตารางเต็มจากต้นทาง):

```text
POST /api/v1/mock-percent-ingest.php
```

payload ตัวอย่างแบบ percent threshold:

```json
{
    "unit_code": "fleet",
    "metrics": [
        {
            "key": "support.efficiency.material.ssot_pon_kr",
            "percent": 85
        }
    ]
}
```

ตัวอย่าง `curl` (mock):

```bash
curl -X POST http://[server-ip]/readiness/api/v1/mock-percent-ingest.php \
    -H "X-Client-Key: default-ingestion-client" \
    -H "X-Client-Secret: change-me-in-production" \
    -H "Content-Type: application/json" \
    -d '{"unit_code":"fleet","metrics":[{"key":"support.efficiency.material.ssot_pon_kr","percent":85}]}'
```

payload ตัวอย่างแบบ status / enum mapping:

```json
{
    "unit_code": "fleet",
    "metrics": [
        {
            "key": "support.efficiency.material.ssot_pon_kr.status",
            "status": "green"
        }
    ]
}
```

payload ตัวอย่างแบบ direct score:

```json
{
    "unit_code": "fleet",
    "metrics": [
        {
            "key": "support.efficiency.material.ssot_pon_kr.score",
            "score": 2
        }
    ]
}
```

ตัวอย่างเรียกผ่าน script (mock):

```bash
APP_URL=http://127.0.0.1:18080 \
MOCK_API_CLIENT_KEY=default-ingestion-client \
MOCK_API_CLIENT_SECRET=change-me-in-production \
MOCK_UNIT_CODE=fleet \
MOCK_METRIC_KEY=support.efficiency.material.ssot_pon_kr \
MOCK_METRIC_FIELD=percent \
MOCK_METRIC_VALUE=85 \
./scripts/mock-percent-ingest.sh
```

ตัวอย่างเรียกผ่าน script แบบ status:

```bash
APP_URL=http://127.0.0.1:18080 \
MOCK_API_CLIENT_KEY=default-ingestion-client \
MOCK_API_CLIENT_SECRET=change-me-in-production \
MOCK_UNIT_CODE=fleet \
MOCK_METRIC_KEY=support.efficiency.material.ssot_pon_kr.status \
MOCK_METRIC_FIELD=status \
MOCK_METRIC_VALUE=green \
./scripts/mock-percent-ingest.sh
```

กติกา transform ที่รองรับ:

- `percent_threshold`: `percent >= readiness_ready_threshold` ได้ `2`, `percent >= readiness_warning_threshold` ได้ `1`, น้อยกว่านั้นได้ `0`
- `enum_map`: map ค่า text เช่น `green/yellow/red` ไปเป็น `2/1/0` ตาม `transform_payload`
- `direct_score`: รับค่า `score` ตรงในช่วง `0-2`

หมายเหตุสำหรับ mock endpoint:

- metric key จะ map ตามตาราง `mock_percent_metric_mappings` ในฐานข้อมูลเป็นหลัก
- ถ้ายังไม่มีการตั้งค่าในฐานข้อมูล ระบบจะ fallback ไปใช้ `MOCK_PERCENT_METRIC_MAP` ใน `config.php`
- เริ่มต้นมี map ตัวอย่าง: `support.efficiency.material.ssot_pon_kr` -> `efficiency.material[1]`
- สามารถเพิ่ม/แก้ไข/ปิดใช้งาน metric mapping ได้จากหน้า `admin/mock-percent-mappings.php` โดยไม่ต้องแก้โค้ดทุกครั้ง
- mapping แต่ละรายการกำหนด `transform_type` และ `transform_payload` ได้ เช่น `{"map":{"green":2,"yellow":1,"red":0}}`
- หน้า admin รองรับ export/import mapping เป็น JSON หรือ CSV และเตือนเมื่อหลาย metric key ชน target readiness item เดียวกัน
- endpoint นี้ออกแบบเพื่อ mock/adapter ระหว่างรอระบบต้นทางจริง

## 9. Smoke test สำหรับ staging/CI

อย่ารัน smoke test โดยอ้างอิง `.env` production โดยตรงอีกต่อไป ให้ใช้ env แยก เช่น `.env.ci` หรือ `.env.staging` ผ่านตัวแปร `SMOKE_ENV_FILE`

เตรียม fixtures สำหรับ smoke test:

```bash
SMOKE_ENV_FILE=.env.ci ./scripts/prepare-smoke-fixtures.sh
```

รัน smoke test บน staging/CI โดยให้ script สร้างและ cleanup fixtures อัตโนมัติ:

```bash
SMOKE_ENV_FILE=.env.ci \
APP_URL=http://127.0.0.1:18080 \
SMOKE_AUTO_PREPARE=1 \
SMOKE_AUTO_CLEANUP=1 \
./scripts/smoke-test.sh
```

ถ้าจำเป็นต้องล้าง fixtures ที่สร้างไว้แล้ว:

```bash
SMOKE_ENV_FILE=.env.staging ./scripts/cleanup-smoke-fixtures.sh
```

หมายเหตุ:

- `scripts/smoke-test.sh` จะปฏิเสธ `APP_ENV=production` โดย default
- ถ้าจำเป็นจริงๆ ต้องตั้ง `SMOKE_ALLOW_PRODUCTION=1` เองอย่างชัดเจน
- smoke test จะทดสอบ endpoint `api/v1/mock-percent-ingest.php` ด้วยโดย default (ปิดได้ด้วย `SMOKE_TEST_MOCK_PERCENT=0`)
- GitHub Actions workflow ตัวอย่างอยู่ที่ `.github/workflows/smoke-test.yml`
- ค่า default ของ fixture คือ `smoke_admin` และ `smoke-client-fixed`

## 10. จัดการรายการประเมิน

- หน้า Dashboard, Input และ API validation ใช้รายการจากตาราง `readiness_items` เป็นหลัก
- หน้า `admin/readiness-items.php` ใช้เพิ่ม, แก้ไข, ลบ รายการจากฐานข้อมูลโดยตรง
- หน้า `admin/readiness-display-settings.php` ใช้กำหนดว่าแต่ละมิติจะถูกแสดงหรือถูกใช้คำนวณหรือไม่ โดยบันทึกลงฐานข้อมูลและ override ค่า env/default
- หน้า `admin/readiness-sync.php` ใช้ preview diff ระหว่าง config กับ DB, export backup และ apply sync แบบมี guard
- หน้า `admin/readiness-items.php` รองรับ reorder รายการในคอลัมน์เดียวกัน และจะเลื่อน `item_index` ใน `readiness_entries` ให้สัมพันธ์กันด้วย
- การลบรายการจะลบค่าประเมินของรายการนั้นจาก `readiness_entries` ทุกหน่วย และ reindex ลำดับ item ที่เหลือในคอลัมน์เดียวกัน
- ถ้าต้องการ sync รายการจาก `ITEMS` ใน `config.php` ลงฐานข้อมูล ให้ตั้ง `READINESS_SYNC_ITEMS_FROM_CONFIG=1` ชั่วคราวแล้ว reload หนึ่งครั้ง จากนั้นควรปิดกลับเป็น `0`
- การ sync จาก config เหมาะกับการ seed ครั้งแรกหรือแก้ label/url เป็นหลัก ถ้ามีข้อมูลใช้งานแล้วไม่ควร reorder หรือลบ item จาก config โดยตรง
- ทุกครั้งที่ apply sync ผ่าน script หรือหน้า admin ระบบจะสร้าง backup ของ `readiness_items` และ `readiness_entries` ก่อนเสมอไว้ที่ `data/backups/readiness-sync/`
- ถ้ามีรายการที่ถูกอัปเดตหรือปิดใช้งานและมีข้อมูลจริงผูกอยู่ใน `readiness_entries` หน้า admin จะแสดง usage risk และบังคับยืนยันก่อน apply
- หน้า Dashboard และ Input แสดงทั้ง `คะแนนมิติ` และ `ผลต่อคะแนนรวมจริง` แยกกัน โดย `ผลต่อคะแนนรวมจริง` คือคะแนนมิติหลังคูณน้ำหนักของมิตินั้นแล้ว
- ท้ายตารางในหน้า Dashboard และ Input มีแถว `รวมตามแกนแนวตั้ง` เพื่อสรุปคะแนนขององค์บุคคล, องค์วัตถุ และองค์ยุทธวิธี พร้อมผลต่อคะแนนรวมจริง
- สามารถกำหนดมิติที่จะแสดงและมิติที่ใช้คำนวณได้ผ่านหน้า `admin/readiness-display-settings.php` และถ้ายังไม่ตั้งค่าในฐานข้อมูล ระบบจะ fallback ไปใช้ `READINESS_VISIBLE_ROWS` และ `READINESS_CALCULATED_ROWS`

ตัวอย่าง script สำหรับ preview/apply config -> DB:

```bash
./scripts/sync-readiness-items-from-config.sh preview
ALLOW_PRODUCTION_SYNC=1 ./scripts/sync-readiness-items-from-config.sh apply
```

ในโหมด `preview` script จะแสดง diff จริงว่าอะไรจะถูกเพิ่ม, แก้ไข หรือปิดใช้งานก่อน `apply`

ถ้าใช้ env file แยก:

```bash
SYNC_ENV_FILE=.env.staging ./scripts/sync-readiness-items-from-config.sh preview
```

restore จาก backup:

```bash
ALLOW_PRODUCTION_SYNC=1 ./scripts/restore-readiness-sync-backup.sh data/backups/readiness-sync/readiness-sync-YYYYmmdd-HHMMSS-xxxxxxxx.json
```

แนะนำให้ใช้ `preview` ก่อนทุกครั้ง, เก็บไฟล์ backup ไว้ภายนอกเครื่องอย่างน้อย 1 ชุด และทำ restore ผ่าน shell เท่านั้นเพื่อลดความเสี่ยงจากการกดผิดในหน้าเว็บ

## 11. Hardening สำหรับ production

ควรตั้งค่าต่อไปนี้ก่อนใช้งานจริง:

1. ใช้ HTTPS ทั้งระบบ
2. ตั้ง `READINESS_APP_ENV=production`
3. ใช้ MySQL/MariaDB แทน SQLite
4. เปลี่ยนบัญชี seed เริ่มต้นทันทีหลัง deploy
5. rotate API secrets ทุกครั้งหลังทดสอบหรือสงสัยว่ารั่ว
6. จำกัด `allowed_ips` ของ API clients ให้แคบที่สุด
7. เปิด backup ของ DB และตรวจสอบการ restore ได้จริง
8. ตรวจหน้า `admin/audit-log.php` เป็นประจำ

## 12. Backup

ถ้าใช้ SQLite:

```bash
crontab -e
0 23 * * * cp /var/www/html/readiness/data/readiness.sqlite /var/backups/readiness-$(date +\%Y\%m\%d).sqlite
```

ถ้าใช้ MySQL/MariaDB:

```bash
mysqldump -u readiness_user -p readiness > readiness.sql
```

## 13. โครงสร้างไฟล์หลัก

```text
readiness/
├── .github/
│   └── workflows/
│       └── smoke-test.yml
├── index.php
├── input.php
├── login.php
├── logout.php
├── config.php
├── functions.php
├── admin/
│   ├── audit-log.php
│   ├── api-clients.php
│   ├── mock-percent-mappings.php
│   ├── readiness-items.php
│   ├── units.php
│   └── users.php
├── api/
│   └── v1/
│       ├── ingest.php
│       └── mock-percent-ingest.php
├── scripts/
│   ├── cleanup-smoke-fixtures.sh
│   ├── mock-percent-ingest.sh
│   ├── prepare-smoke-fixtures.sh
│   ├── restore-readiness-sync-backup.sh
│   ├── sync-readiness-items-from-config.sh
│   └── smoke-test.sh
└── data/
    ├── .htaccess
    ├── readiness.json
    └── readiness.sqlite
```
>>>>>>> 1bc2f7d (Start Project)

# ระบบสอบเลื่อนฐานะทหาร (Military Promotion Examination System)

## 📖 เอกสารสมบูรณ์สำหรับการพัฒนาระบบ

**เวอร์ชัน:** 1.0  
**วันที่อัปเดตล่าสุด:** 5 กุมภาพันธ์ 2026  
**ผู้จัดทำ:** Development Team

---

## 📋 สารบัญ

1. [ภาพรวมระบบ](#1-ภาพรวมระบบ)
2. [Features และ Requirements ทั้งหมด](#2-features-และ-requirements-ทั้งหมด)
3. [RBAC และสิทธิ์การใช้งาน](#3-rbac-และสิทธิ์การใช้งาน)
4. [Tech Stack และ Dependencies](#4-tech-stack-และ-dependencies)
5. [Database Schema](#5-database-schema)
6. [Project Structure](#6-project-structure)
7. [UI/UX Design Guidelines](#7-uiux-design-guidelines)
8. [Security และ Middleware](#8-security-และ-middleware)
9. [Business Logic และ Services](#9-business-logic-และ-services)
10. [API และ Routes](#10-api-และ-routes)
11. [Testing Strategy](#11-testing-strategy)
12. [Deployment และ Configuration](#12-deployment-และ-configuration)

---

## 1. ภาพรวมระบบ

### 1.1 วัตถุประสงค์

ระบบจัดการการสอบเลื่อนฐานะทหารออนไลน์ สำหรับการลงทะเบียนผู้เข้าสอบ การจัดการข้อมูล การออกหมายเลขประจำตัวสอบ และการรายงานผลการสอบ

### 1.2 ผู้ใช้งานหลัก (3 Roles)

| Role                           | จำนวน    | วิธีสร้างบัญชี               | หน้าที่หลัก                      |
| ------------------------------ | -------- | ---------------------------- | -------------------------------- |
| **Examinee** (ผู้เข้าสอบ)      | ไม่จำกัด | สมัครเองผ่าน Public Register | ลงทะเบียนและเข้าสอบ              |
| **Staff** (เจ้าหน้าที่)        | จำกัด    | Staff สร้างให้               | จัดการระบบทั้งหมด (Full Control) |
| **Commander** (ผู้บังคับบัญชา) | จำกัด    | Staff สร้างให้               | ดูรายงานและสถิติ (Read-only)     |

### 1.3 ระดับการสอบ

- **จ่าเอก (Sergeant Major)**
- **พันจ่าเอก (Master Sergeant)**

### 1.4 Color Theme (ตามที่กำหนด)

**สีหลัก - เขียวเข้ม (Dark Green)**

- Primary-600: `#1B4332`
- Primary-500: `#2D6A4F`
- Primary-700: `#14532d`

**สีรอง - เหลือง Pastel**

- Secondary-100: `#FEF3C7`
- Secondary-200: `#FDE68A`
- Secondary-300: `#FCD34D`

---

## 2. Features และ Requirements ทั้งหมด

### 2.1 Authentication System (ระบบยืนยันตัวตน)

#### 2.1.1 Login

- **เข้าถึงได้:** Public (ทุกคน)
- **ฟิลด์:**
    - หมายเลขประจำตัว 13 หลัก
    - รหัสผ่าน
- **Features:**
    - Remember me checkbox
    - Rate limiting: 5 attempts / 15 minutes
    - Auto redirect ตาม role

#### 2.1.2 Register สำหรับผู้เข้าสอบ

- **เข้าถึงได้:** Public (ทุกคน)
- **ฟิลด์บังคับ:**
    - หมายเลขประจำตัว 13 หลัก (unique, indexed)
    - ยศ
    - ชื่อ
    - นามสกุล
    - รหัสผ่าน (min 8 characters)
    - ยืนยันรหัสผ่าน
- **Validation:**
    - หมายเลขประจำตัวต้องไม่ซ้ำ
    - รหัสผ่านต้องตรงกัน
- **Process:**
    - Hash password (bcrypt)
    - สร้าง user role = 'examinee'
    - Auto-login หลังสมัครสำเร็จ

#### 2.1.3 Register สำหรับ Staff/Commander (Admin Only)

- **เข้าถึงได้:** Staff เท่านั้น
- **URL:** `/staff/users/create`
- **ฟิลด์บังคับ:**
    - หมายเลขประจำตัว 13 หลัก
    - ยศ
    - ชื่อ - นามสกุล
    - Email (optional แต่แนะนำ)
    - Role (staff/commander)
    - รหัสผ่าน (manual หรือ auto-generate)
- **Process:**
    - บันทึก `created_by` = staff_user_id
    - ส่ง email แจ้ง credentials (optional)

---

### 2.2 Exam Registration (ระบบลงทะเบียนสอบ)

#### 2.2.1 การสมัครสอบครั้งแรก

- **Role:** Examinee
- **URL:** `/examinee/register-exam`
- **ข้อมูลที่ต้องกรอก:**

| ฟิลด์               | ประเภท   | Required | คำอธิบาย                     |
| ------------------- | -------- | -------- | ---------------------------- |
| ตำแหน่ง             | Text     | ✅       | ตำแหน่งปัจจุบัน              |
| เหล่า               | Dropdown | ✅       | จาก master `branches`        |
| อายุ                | Number   | ✅       | อายุปัจจุบัน                 |
| ปีที่มีสิทธิ์สอบ    | Year     | ✅       | Year picker                  |
| ปีที่ถูกงดบำเหน็จ   | Number   | ✅       | Default = 0                  |
| พื้นที่ราชการชายแดน | Dropdown | -        | Optional, จาก `border_areas` |
| สถานที่สอบ          | Dropdown | ✅       | จาก `test_locations`         |
| ระดับที่สอบ         | Radio    | ✅       | จ่าเอก/พันจ่าเอก             |

**คะแนนที่คำนวณอัตโนมัติ:**

```
คะแนนค้างบรรจุ = (ปีปัจจุบัน - ปีที่มีสิทธิ์สอบ) - ปีที่ถูกงดบำเหน็จ
คะแนนพิเศษ = ดึงจาก border_areas.special_score
คะแนนรวม = คะแนนค้างบรรจุ + คะแนนพิเศษ
```

**Business Rules:**

- ✅ ตรวจสอบว่าอยู่ในช่วงเวลาเปิด-ปิดรับสมัคร
- ✅ 1 คนสมัครได้ 1 รอบสอบเท่านั้น
- ✅ บันทึกลง `exam_registrations` status = 'pending'

#### 2.2.2 นำเข้าข้อมูลปีที่แล้ว

- **Feature:** ปุ่ม "ใช้ข้อมูลปีที่แล้ว"
- **Process:**
    1. ค้นหา `exam_registrations` ของผู้ใช้จากปีที่แล้ว
    2. ดึงข้อมูล: ตำแหน่ง, เหล่า, สถานที่สอบ
    3. Pre-fill ฟอร์ม
    4. ให้แก้ไขก่อน submit

#### 2.2.3 แก้ไขข้อมูลส่วนตัว (Examinee)

- **Role:** Examinee
- **URL:** `/examinee/profile`
- **ฟิลด์ที่แก้ไขได้:**
    - ยศ, ชื่อ, นามสกุล
    - ตำแหน่ง, เหล่า, อายุ
    - สถานที่สอบ (ก่อนปิดรับสมัครเท่านั้น)
    - พื้นที่ชายแดน (ก่อนปิดรับสมัครเท่านั้น)
- **ห้ามแก้ไข:**
    - หมายเลขประจำตัว
    - หมายเลขสอบ (ถ้ามีแล้ว)

#### 2.2.4 แก้ไขข้อมูลผู้เข้าสอบ (Staff)

- **Role:** Staff
- **URL:** `/staff/examinees/{id}/edit`
- **ฟิลด์ที่แก้ไขได้:** ทุกฟิลด์ ยกเว้นหมายเลขประจำตัว
- **ฟิลด์พิเศษ:**
    - เหตุผลในการแก้ไข (reason) - Required
- **Process:**
    1. แก้ไขข้อมูลใน `examinees`
    2. บันทึก log ใน `examinee_edit_logs`:
        - field_name
        - old_value
        - new_value
        - edited_by
        - reason
        - edited_at
    3. Spatie Activity Log จะ log อัตโนมัติด้วย

#### 2.2.5 เพิ่มผู้สมัครหลังปิดรับสมัคร (Staff)

- **Role:** Staff
- **URL:** `/staff/examinees/add-late`
- **Process:**
    - สามารถเพิ่มได้แม้หลังปิดรับสมัคร
    - หมายเลขสอบจะต่อท้ายจากหมายเลขสุดท้าย
    - ต้องระบุเหตุผล

---

### 2.3 Exam Session Management (จัดการรอบสอบ)

#### 2.3.1 สร้างรอบสอบ

- **Role:** Staff
- **URL:** `/staff/exam-sessions/create`
- **ฟิลด์:**

| ฟิลด์            | ประเภท  | คำอธิบาย                         |
| ---------------- | ------- | -------------------------------- |
| ปีการสอบ         | Year    | ปี พ.ศ.                          |
| ระดับการสอบ      | Enum    | sergeant_major / master_sergeant |
| วันเริ่มรับสมัคร | Date    | registration_start               |
| วันปิดรับสมัคร   | Date    | registration_end                 |
| วันสอบ           | Date    | exam_date                        |
| สถานะ            | Boolean | is_active                        |

**Validation:**

- 1 ปี 1 ระดับมีได้แค่ 1 รอบสอบเท่านั้น (unique constraint)
- วันปิดรับสมัคร > วันเริ่มรับสมัคร
- วันสอบ > วันปิดรับสมัคร

#### 2.3.2 กำหนดอัตราที่เปิดสอบ

- **Role:** Staff
- **URL:** `/staff/position-quotas/manage`
- **ฟิลด์:**
    - รอบสอบ (exam_session_id)
    - ตำแหน่ง (position_name)
    - จำนวนที่เปิดรับ (quota_count)

#### 2.3.3 กำหนดสถานที่สอบ

- **Role:** Staff
- **URL:** `/staff/test-locations/manage`
- **CRUD Operations:**
    - Create: เพิ่มสถานที่ใหม่
    - Read: รายการสถานที่
    - Update: แก้ไขข้อมูล
    - Delete: ลบ (ถ้าไม่มีผู้ใช้)
- **ฟิลด์:**

| ฟิลด์          | ประเภท  | คำอธิบาย  |
| -------------- | ------- | --------- |
| ชื่อสถานที่    | String  | name      |
| รหัสสถานที่    | Char(1) | code: 1-9 |
| ที่อยู่        | Text    | address   |
| จำนวนที่รับได้ | Integer | capacity  |
| สถานะ          | Boolean | is_active |

#### 2.3.4 จัดการเหล่า

- **Role:** Staff
- **URL:** `/staff/branches/manage`
- **CRUD Operations**
- **ฟิลด์:**

| ฟิลด์     | ประเภท  | ตัวอย่าง             |
| --------- | ------- | -------------------- |
| ชื่อเหล่า | String  | ทหารราบ, ทหารปืนใหญ่ |
| รหัสเหล่า | Char(1) | 1-9                  |
| สถานะ     | Boolean | is_active            |

**Master Data:**

```
1 = ทหารราบ
2 = ทหารปืนใหญ่
3 = ทหารช่าง
4 = ทหารสื่อสาร
5 = ทหารขนส่ง
```

---

### 2.4 Border Area Management (จัดการพื้นที่ชายแดน) 🔥

#### 2.4.1 รายการพื้นที่ชายแดน

- **URL:** `/staff/border-areas`
- **สิทธิ์:**
    - **Staff:** CRUD (Create, Read, Update, Delete)
    - **Commander:** Read-only
    - **Examinee:** Select dropdown only

**Table Display:**
| คอลัมน์ | คำอธิบาย |
|---------|----------|
| รหัสพื้นที่ | code (BA01, BA02, ...) |
| ชื่อพื้นที่ | name (จ.นราธิวาส, จ.ยะลา, ...) |
| คะแนนพิเศษ | special_score (5.00, 4.50, ...) |
| สถานะ | is_active (เปิด/ปิด) |
| จัดการ | Edit, Delete buttons |

#### 2.4.2 เพิ่มพื้นที่ชายแดน

- **Role:** Staff
- **URL:** `/staff/border-areas/create`
- **ฟิลด์:**

| ฟิลด์       | ประเภท       | Required | คำอธิบาย            |
| ----------- | ------------ | -------- | ------------------- |
| รหัสพื้นที่ | String(10)   | ✅       | Unique (BA01, BA02) |
| ชื่อพื้นที่ | String(255)  | ✅       | จ.นราธิวาส          |
| คะแนนพิเศษ  | Decimal(5,2) | ✅       | 0.00-99.99          |
| รายละเอียด  | Text         | -        | Description         |
| สถานะ       | Boolean      | ✅       | Default: true       |

**Process:**

1. Validate รหัสพื้นที่ไม่ซ้ำ
2. บันทึกลง `border_areas`
3. Set `created_by` = current staff user_id

#### 2.4.3 แก้ไขพื้นที่ชายแดน

- **Role:** Staff
- **URL:** `/staff/border-areas/{id}/edit`
- **ฟิลด์:** เหมือนการเพิ่ม + เหตุผลในการเปลี่ยนแปลง (ถ้าเปลี่ยนคะแนน)

**Process:**

1. แก้ไขข้อมูลใน `border_areas`
2. Set `updated_by` = current staff user_id
3. **ถ้าเปลี่ยนคะแนน** → บันทึกลง `border_area_score_history`:
    - old_score
    - new_score
    - changed_by
    - reason
    - changed_at

#### 2.4.4 ประวัติการเปลี่ยนแปลงคะแนน

- **Role:** Staff, Commander
- **URL:** `/staff/border-areas/history`
- **แสดง:**

| คอลัมน์          | คำอธิบาย              |
| ---------------- | --------------------- |
| วันที่เปลี่ยน    | changed_at            |
| ชื่อพื้นที่      | border_area.name      |
| คะแนนเดิม → ใหม่ | old_score → new_score |
| ผู้เปลี่ยน       | changed_by.name       |
| เหตุผล           | reason                |

#### 2.4.5 ลบพื้นที่ชายแดน

- **Role:** Staff
- **Process:**
    1. ตรวจสอบว่าไม่มีผู้สมัครใช้อยู่
    2. ถ้ามี → แจ้งเตือนและไม่ให้ลบ
    3. ถ้าไม่มี → Soft delete (set deleted_at)

**Master Data ตัวอย่าง:**

```php
BA01 = จ.นราธิวาส (5.00 คะแนน)
BA02 = จ.ยะลา (4.50 คะแนน)
BA03 = จ.ปัตตานี (4.50 คะแนน)
BA04 = จ.สงขลา บางพื้นที่ (3.00 คะแนน)
BA05 = จ.เชียงราย ชายแดน (2.50 คะแนน)
BA06 = จ.ตาก ชายแดน (2.00 คะแนน)
```

---

### 2.5 Exam Number Generation (สร้างหมายเลขสอบ)

#### 2.5.1 กฎการออกหมายเลข (5 หลัก)

**Format: XYZNN**

- **X (หลักที่ 1):** รหัสสถานที่สอบ (1 หลัก: 1-9)
- **Y (หลักที่ 2):** รหัสเหล่า (1 หลัก: 1-9)
- **ZNN (หลักที่ 3-5):** ลำดับเรียงตามชื่อ (001-999)

#### 2.5.2 ตัวอย่างหมายเลข

| สถานที่สอบ    | เหล่า           | ชื่อ        | หมายเลขสอบ |
| ------------- | --------------- | ----------- | ---------- |
| 1 (กทม.)      | 1 (ทหารราบ)     | นาย ก มีสุข | **11001**  |
| 1 (กทม.)      | 1 (ทหารราบ)     | นาย ข ใจดี  | **11002**  |
| 1 (กทม.)      | 2 (ทหารปืนใหญ่) | นาย ก มีสุข | **12001**  |
| 2 (เชียงใหม่) | 1 (ทหารราบ)     | นาย ก มีสุข | **21001**  |

#### 2.5.3 การสร้างหมายเลข

- **Role:** Staff
- **URL:** `/staff/generate-exam-numbers`
- **Input:** เลือก exam_session_id
- **Algorithm:**
    1. ดึงผู้สมัครทั้งหมดที่ status = 'pending'
    2. Group by (test_location.code, branch.code)
    3. แต่ละ group → Sort by user.first_name ASC
    4. Generate หมายเลข: `locationCode + branchCode + sequence`
    5. Update `exam_registrations.exam_number`
    6. Update status = 'confirmed'
- **Output:** จำนวนหมายเลขที่สร้างสำเร็จ

---

### 2.6 Dashboard และรายงาน

#### 2.6.1 Dashboard ผู้เข้าสอบ

- **URL:** `/examinee/dashboard`
- **แสดง:**
    - 📊 สถานะการสมัคร (Widget)
        - สมัครแล้ว / ยังไม่สมัคร
        - หมายเลขประจำตัวสอบ
        - สถานที่สอบ
        - วันที่สอบ
    - 🎯 คะแนนรวม
        - คะแนนค้างบรรจุ
        - คะแนนพิเศษ (จากพื้นที่ชายแดน)
        - คะแนนรวมทั้งหมด
    - 🔽 Actions
        - ปุ่ม "ดาวน์โหลดบัตรประจำตัวสอบ" (PDF)
        - ปุ่ม "ประวัติการสอบ"
        - ปุ่ม "แก้ไขข้อมูลส่วนตัว"

#### 2.6.2 Dashboard เจ้าหน้าที่

- **URL:** `/staff/dashboard`
- **แสดง:**

**สรุปรอบสอบปัจจุบัน (Cards):**

- จำนวนผู้สมัครทั้งหมด
- จำนวนที่ออกหมายเลขแล้ว
- จำนวนที่รอออกหมายเลข
- จำนวนที่ยกเลิก

**กราฟและแผนภูมิ:**

- 📊 Bar Chart: จำนวนผู้สมัครแยกตามสถานที่สอบ
- 🥧 Pie Chart: จำนวนผู้สมัครแยกตามเหล่า
- 🍩 Donut Chart: จำนวนผู้สมัครแยกตามระดับ (จ่าเอก/พันจ่าเอก)

**ตารางสถิติ:**
| เหล่า | จำนวนผู้สมัคร | ออกหมายเลขแล้ว | รอดำเนินการ |
|------|--------------|---------------|-------------|
| ทหารราบ | 150 | 150 | 0 |
| ทหารปืนใหญ่ | 80 | 80 | 0 |
| ... | ... | ... | ... |

**ฟิลเตอร์:**

- ปีสอบ
- สถานที่สอบ
- เหล่า
- ตำแหน่ง
- ระดับ (จ่าเอก/พันจ่าเอก)

#### 2.6.3 Dashboard ผู้บังคับบัญชา

- **URL:** `/commander/dashboard`
- **แสดง:** เหมือน Staff Dashboard แต่ Read-only
- **เพิ่มเติม:**
    - เปรียบเทียบกับปีก่อน (YoY comparison)
    - Export รายงาน Executive Summary (PDF)

---

### 2.7 Report System (ระบบรายงาน)

#### 2.7.1 พิมพ์รายชื่อผู้สอบ (แยกตามสถานที่)

- **URL:** `/staff/reports/examinees-by-location`
- **Role:** Staff, Commander
- **ฟิลเตอร์:**
    - สถานที่สอบ (Required)
    - เหล่า (Optional)
    - ระดับการสอบ (Optional)
- **Output:** PDF
- **เนื้อหา:**

**Header:**

- ชื่อสถานที่สอบ
- วันที่สอบ
- ระดับการสอบ

**Body - Table:**
| หมายเลขสอบ | ยศ ชื่อ-นามสกุล | หมายเลขประจำตัว | เหล่า | คะแนนรวม |
|-----------|-----------------|-----------------|------|----------|
| 11001 | จ.ส.อ. สมชาย ใจดี | 1234567890123 | ทหารราบ | 5.50 |
| ... | ... | ... | ... | ... |

**Footer:**

- จำนวนผู้สอบทั้งหมด
- วันที่พิมพ์รายงาน
- ผู้พิมพ์รายงาน

#### 2.7.2 Export ข้อมูลผู้สมัครทั้งหมด

- **URL:** `/staff/reports/export-all`
- **Role:** Staff
- **Format:** Excel (.xlsx)
- **Sheets:**
    1. **ผู้สมัครทั้งหมด** - All fields
    2. **แยกตามสถานที่สอบ** - Group by location
    3. **สรุปสถิติ** - Summary statistics

---

### 2.8 History และ Archive (ประวัติและจัดเก็บข้อมูล)

#### 2.8.1 ดูประวัติการสอบ (Examinee)

- **URL:** `/examinee/history`
- **แสดง:**

| ปีที่สอบ | ระดับ     | สถานที่สอบ | หมายเลขสอบ | คะแนนรวม | สถานะ |
| -------- | --------- | ---------- | ---------- | -------- | ----- |
| 2566     | จ่าเอก    | กรุงเทพฯ   | 11045      | 4.50     | ผ่าน  |
| 2567     | พันจ่าเอก | กรุงเทพฯ   | 12078      | 6.00     | รอผล  |

#### 2.8.2 Archive ข้อมูลปีเก่า (Staff)

- **URL:** `/staff/archive/{year}`
- **Role:** Staff
- **Process:**
    1. Mark `exam_session.is_archived = true`
    2. ข้อมูลยังอยู่ในระบบ
    3. ไม่แสดงใน dropdown selection
    4. สามารถ restore ได้

---

## 3. RBAC และสิทธิ์การใช้งาน

### 3.1 สรุปบทบาทผู้ใช้

| Role          | การสร้างบัญชี                          | Login Method               | Dashboard              |
| ------------- | -------------------------------------- | -------------------------- | ---------------------- |
| **Examinee**  | สมัครเองผ่าน `/register`               | หมายเลข 13 หลัก + รหัสผ่าน | `/examinee/dashboard`  |
| **Staff**     | Staff สร้างให้ใน `/staff/users/create` | หมายเลข 13 หลัก + รหัสผ่าน | `/staff/dashboard`     |
| **Commander** | Staff สร้างให้ใน `/staff/users/create` | หมายเลข 13 หลัก + รหัสผ่าน | `/commander/dashboard` |

---

### 3.2 Permission Matrix (ตารางสิทธิ์แบบละเอียด)

| Feature                              | Examinee    | Staff | Commander |
| ------------------------------------ | ----------- | ----- | --------- |
| **Authentication & User Management** |
| สมัครบัญชีเอง (Public Register)      | ✅          | ❌    | ❌        |
| Login                                | ✅          | ✅    | ✅        |
| Logout                               | ✅          | ✅    | ✅        |
| เปลี่ยนรหัสผ่านตัวเอง                | ✅          | ✅    | ✅        |
| สร้างบัญชี Staff/Commander           | ❌          | ✅    | ❌        |
| **Profile Management**               |
| ดูข้อมูลตัวเอง                       | ✅          | ✅    | ✅        |
| แก้ไขข้อมูลตัวเอง                    | ✅          | ✅    | ✅        |
| ดูข้อมูลผู้อื่น                      | ❌          | ✅    | ✅ Read   |
| แก้ไขข้อมูลผู้อื่น                   | ❌          | ✅    | ❌        |
| **Exam Registration**                |
| ลงทะเบียนสอบ                         | ✅          | ✅    | ❌        |
| เลือกสถานที่สอบ                      | ✅          | ✅    | ❌        |
| เลือกพื้นที่ชายแดน                   | ✅ Select   | ✅    | ❌        |
| ยกเลิกการสมัคร                       | ✅          | ✅    | ❌        |
| เพิ่มผู้สมัครหลังปิดรับสมัคร         | ❌          | ✅    | ❌        |
| **Exam Session Management**          |
| ดูรอบสอบปัจจุบัน                     | ✅          | ✅    | ✅        |
| สร้างรอบสอบ                          | ❌          | ✅    | ❌        |
| แก้ไขรอบสอบ                          | ❌          | ✅    | ❌        |
| ปิดรอบสอบ                            | ❌          | ✅    | ❌        |
| **Border Area Management** 🔥        |
| ดูรายการพื้นที่ชายแดน                | ✅ Dropdown | ✅    | ✅ Read   |
| เพิ่มพื้นที่ชายแดน                   | ❌          | ✅    | ❌        |
| แก้ไขพื้นที่ชายแดน                   | ❌          | ✅    | ❌        |
| กำหนดคะแนนพิเศษ                      | ❌          | ✅    | ❌        |
| ลบพื้นที่ชายแดน                      | ❌          | ✅    | ❌        |
| ดูประวัติการเปลี่ยนคะแนน             | ❌          | ✅    | ✅        |
| **Exam Number Generation**           |
| ดูหมายเลขสอบตัวเอง                   | ✅          | ✅    | ❌        |
| สร้างหมายเลขสอบ                      | ❌          | ✅    | ❌        |
| **Test Location Management**         |
| ดูรายการสถานที่สอบ                   | ✅          | ✅    | ✅        |
| เพิ่มสถานที่สอบ                      | ❌          | ✅    | ❌        |
| แก้ไขสถานที่สอบ                      | ❌          | ✅    | ❌        |
| ลบสถานที่สอบ                         | ❌          | ✅    | ❌        |
| **Branch Management**                |
| ดูรายการเหล่า                        | ✅          | ✅    | ✅        |
| เพิ่มเหล่า                           | ❌          | ✅    | ❌        |
| แก้ไขเหล่า                           | ❌          | ✅    | ❌        |
| ลบเหล่า                              | ❌          | ✅    | ❌        |
| **Position Quota Management**        |
| ดูอัตราที่เปิดสอบ                    | ✅          | ✅    | ✅        |
| กำหนดอัตราที่เปิดสอบ                 | ❌          | ✅    | ❌        |
| **Dashboard & Reports**              |
| ดู Dashboard ตัวเอง                  | ✅          | ✅    | ✅        |
| ดูสถิติทั้งหมด                       | ❌          | ✅    | ✅        |
| พิมพ์รายชื่อผู้สอบ                   | ❌          | ✅    | ✅        |
| Export ข้อมูล Excel                  | ❌          | ✅    | ✅        |
| Export รายงาน PDF                    | ❌          | ✅    | ✅        |
| **History & Archive**                |
| ดูประวัติการสอบตัวเอง                | ✅          | ✅    | ❌        |
| ดูประวัติการสอบทั้งหมด               | ❌          | ✅    | ✅        |
| Archive ข้อมูลเก่า                   | ❌          | ✅    | ❌        |
| **Audit Logs**                       |
| ดู Activity Log                      | ❌          | ✅    | ✅ Read   |
| ดู Edit Logs                         | ❌          | ✅    | ✅ Read   |

---

### 3.3 Permissions List (สำหรับ Spatie Permission)

```php
// database/seeders/RolePermissionSeeder.php

$permissions = [
    // Authentication
    'self_register',
    'create_staff_account',
    'create_commander_account',

    // Dashboard
    'view_own_dashboard',
    'view_staff_dashboard',
    'view_commander_dashboard',

    // Examinee Management
    'view_own_profile',
    'edit_own_profile',
    'view_all_examinees',
    'edit_examinee',
    'delete_examinee',

    // Exam Registration
    'register_exam',
    'cancel_exam_registration',
    'add_late_examinee',

    // Exam Session
    'view_exam_sessions',
    'create_exam_session',
    'edit_exam_session',
    'archive_exam_session',

    // Border Area
    'view_border_areas',
    'create_border_area',
    'edit_border_area',
    'delete_border_area',
    'set_special_scores',
    'view_border_area_history',

    // Test Location
    'view_test_locations',
    'create_test_location',
    'edit_test_location',
    'delete_test_location',

    // Branch
    'view_branches',
    'create_branch',
    'edit_branch',
    'delete_branch',

    // Position Quota
    'view_position_quotas',
    'manage_position_quotas',

    // Exam Numbers
    'generate_exam_numbers',
    'view_own_exam_number',

    // Reports
    'view_reports',
    'export_examinee_list',
    'export_statistics',
    'print_exam_card',

    // History
    'view_own_exam_history',
    'view_all_exam_history',

    // Audit
    'view_activity_log',
    'view_edit_logs',
];

// Role Assignment
$rolePermissions = [
    'examinee' => [
        'self_register',
        'view_own_dashboard',
        'view_own_profile',
        'edit_own_profile',
        'register_exam',
        'cancel_exam_registration',
        'view_border_areas', // dropdown only
        'view_exam_sessions',
        'view_test_locations',
        'view_branches',
        'view_position_quotas',
        'view_own_exam_number',
        'view_own_exam_history',
        'print_exam_card',
    ],

    'staff' => [
        // All permissions except some read-only ones
        // (Staff has full control)
    ],

    'commander' => [
        'view_commander_dashboard',
        'view_all_examinees', // read-only
        'view_border_areas',
        'view_border_area_history',
        'view_exam_sessions',
        'view_test_locations',
        'view_branches',
        'view_position_quotas',
        'view_reports',
        'export_examinee_list',
        'export_statistics',
        'view_all_exam_history',
        'view_activity_log', // read-only
        'view_edit_logs', // read-only
    ],
];
```

---

## 4. Tech Stack และ Dependencies

### 4.1 Core Stack

```
Backend: PHP  / Laravel  / Livewire
Frontend: Livewire + Alpine.js + TailwindCSS
```

### 4.2 Required Packages

```bash
# RBAC
composer require spatie/laravel-permission

# PDF Generation
composer require barryvdh/laravel-dompdf

# Excel Import/Export
composer require maatwebsite/excel

# Activity Logging
composer require spatie/laravel-activitylog

# Backup
composer require spatie/laravel-backup

# Livewire Extensions
composer require jantinnerezo/livewire-alert
composer require wire-elements/modal

# UI Components (Choose one)
composer require tallstackui/tallstackui
# OR
composer require wireui/wireui

# Development Tools (Dev only)
composer require laravel/telescope --dev
composer require barryvdh/laravel-debugbar --dev
composer require laravel/pint --dev
```

### 4.3 NPM Packages

```bash
npm install alpinejs
npm install tailwindcss
npm install @tailwindcss/forms
npm install @tailwindcss/typography
npm install apexcharts
npm install chart.js
npm install @heroicons/vue
```

---

## 5. Database Schema

### 5.1 ERD (Entity Relationship Diagram)

```
users ───1:1──→ examinees
  ↓
  └──1:N──→ activity_log
  └──1:N──→ created border_areas (created_by)
  └──1:N──→ updated border_areas (updated_by)
  └──1:N──→ border_area_score_history (changed_by)
  └──1:N──→ examinee_edit_logs (edited_by)

examinees
  ├──N:1──→ branches
  ├──N:1──→ border_areas (nullable)
  └──1:N──→ exam_registrations

exam_sessions
  ├──1:N──→ exam_registrations
  └──1:N──→ position_quotas

exam_registrations
  ├──N:1──→ examinees
  ├──N:1──→ exam_sessions
  ├──N:1──→ test_locations
  └──N:1──→ position_quotas (nullable)

border_areas
  ├──1:N──→ examinees
  └──1:N──→ border_area_score_history

test_locations
  └──1:N──→ exam_registrations

branches
  └──1:N──→ examinees

position_quotas
  ├──N:1──→ exam_sessions
  └──1:N──→ exam_registrations
```

---

### 5.2 Table Schemas (DDL)

#### 5.2.1 users

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    national_id VARCHAR(13) UNIQUE NOT NULL COMMENT 'หมายเลขประจำตัว 13 หลัก',
    rank VARCHAR(100) NOT NULL COMMENT 'ยศ',
    first_name VARCHAR(255) NOT NULL COMMENT 'ชื่อ',
    last_name VARCHAR(255) NOT NULL COMMENT 'นามสกุล',
    email VARCHAR(255) UNIQUE NULLABLE COMMENT 'Email (Staff/Commander)',
    password VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    role ENUM('examinee', 'staff', 'commander') NOT NULL DEFAULT 'examinee',
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULLABLE COMMENT 'ผู้สร้างบัญชี (FK users.id)',
    email_verified_at TIMESTAMP NULLABLE,
    remember_token VARCHAR(100) NULLABLE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,

    INDEX idx_national_id (national_id),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.2 examinees

```sql
CREATE TABLE examinees (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    position VARCHAR(255) NOT NULL COMMENT 'ตำแหน่ง',
    branch_id BIGINT UNSIGNED NOT NULL COMMENT 'FK branches',
    age INT NOT NULL COMMENT 'อายุ',
    eligible_year YEAR NOT NULL COMMENT 'ปีที่มีสิทธิ์สอบ',
    suspended_years INT DEFAULT 0 COMMENT 'ปีที่ถูกงดบำเหน็จ',
    pending_score DECIMAL(5,2) DEFAULT 0 COMMENT 'คะแนนค้างบรรจุ (auto-calculated)',
    special_score DECIMAL(5,2) DEFAULT 0 COMMENT 'คะแนนพิเศษ (จาก border_area)',
    border_area_id BIGINT UNSIGNED NULLABLE COMMENT 'FK border_areas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,

    INDEX idx_user_id (user_id),
    INDEX idx_branch_id (branch_id),
    INDEX idx_border_area_id (border_area_id),
    INDEX idx_eligible_year (eligible_year),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE RESTRICT,
    FOREIGN KEY (border_area_id) REFERENCES border_areas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.3 exam_sessions

```sql
CREATE TABLE exam_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    year YEAR NOT NULL,
    exam_level ENUM('sergeant_major', 'master_sergeant') NOT NULL COMMENT 'จ่าเอก, พันจ่าเอก',
    registration_start DATE NOT NULL,
    registration_end DATE NOT NULL,
    exam_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_archived BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_year_level (year, exam_level),
    INDEX idx_is_active (is_active),
    INDEX idx_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.4 exam_registrations

```sql
CREATE TABLE exam_registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    examinee_id BIGINT UNSIGNED NOT NULL,
    exam_session_id BIGINT UNSIGNED NOT NULL,
    exam_number VARCHAR(5) NULLABLE COMMENT '5 หลัก: XYZNN',
    test_location_id BIGINT UNSIGNED NOT NULL,
    position_quota_id BIGINT UNSIGNED NULLABLE,
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_examinee_session (examinee_id, exam_session_id),
    INDEX idx_exam_session_id (exam_session_id),
    INDEX idx_test_location_id (test_location_id),
    INDEX idx_exam_number (exam_number),
    INDEX idx_status (status),
    FOREIGN KEY (examinee_id) REFERENCES examinees(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (test_location_id) REFERENCES test_locations(id) ON DELETE RESTRICT,
    FOREIGN KEY (position_quota_id) REFERENCES position_quotas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.5 test_locations

```sql
CREATE TABLE test_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code CHAR(1) NOT NULL UNIQUE COMMENT '1-9',
    address TEXT NULLABLE,
    capacity INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,

    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.6 branches

```sql
CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'ทหารราบ, ทหารปืนใหญ่, etc.',
    code CHAR(1) NOT NULL UNIQUE COMMENT '1-9',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,

    INDEX idx_code (code),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.7 border_areas 🔥

```sql
CREATE TABLE border_areas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE COMMENT 'BA01, BA02, etc.',
    name VARCHAR(255) NOT NULL COMMENT 'จ.นราธิวาส, etc.',
    special_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    description TEXT NULLABLE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by BIGINT UNSIGNED NULLABLE COMMENT 'FK users',
    updated_by BIGINT UNSIGNED NULLABLE COMMENT 'FK users',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULLABLE,

    INDEX idx_code (code),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.8 border_area_score_history 🔥

```sql
CREATE TABLE border_area_score_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    border_area_id BIGINT UNSIGNED NOT NULL,
    old_score DECIMAL(5,2) NULLABLE COMMENT 'NULL if new creation',
    new_score DECIMAL(5,2) NOT NULL,
    changed_by BIGINT UNSIGNED NOT NULL COMMENT 'FK users (staff)',
    reason TEXT NULLABLE,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_border_area_id (border_area_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_changed_at (changed_at),
    FOREIGN KEY (border_area_id) REFERENCES border_areas(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.9 examinee_edit_logs 🔥

```sql
CREATE TABLE examinee_edit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    examinee_id BIGINT UNSIGNED NOT NULL,
    edited_by BIGINT UNSIGNED NOT NULL COMMENT 'FK users (staff)',
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT NULLABLE,
    new_value TEXT NULLABLE,
    reason TEXT NULLABLE,
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_examinee_id (examinee_id),
    INDEX idx_edited_by (edited_by),
    INDEX idx_field_name (field_name),
    INDEX idx_edited_at (edited_at),
    FOREIGN KEY (examinee_id) REFERENCES examinees(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.10 position_quotas

```sql
CREATE TABLE position_quotas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_session_id BIGINT UNSIGNED NOT NULL,
    position_name VARCHAR(255) NOT NULL,
    quota_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_exam_session_id (exam_session_id),
    FOREIGN KEY (exam_session_id) REFERENCES exam_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.11 activity_log (Spatie)

```sql
CREATE TABLE activity_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    log_name VARCHAR(255) NULLABLE,
    description TEXT NOT NULL,
    subject_type VARCHAR(255) NULLABLE,
    subject_id BIGINT UNSIGNED NULLABLE,
    causer_type VARCHAR(255) NULLABLE,
    causer_id BIGINT UNSIGNED NULLABLE,
    properties JSON NULLABLE COMMENT 'old/new values',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_causer (causer_type, causer_id),
    INDEX idx_log_name (log_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5.2.12-15 Spatie Permission Tables

```sql
-- roles, permissions, model_has_roles, role_has_permissions
-- (Standard Spatie Permission tables - จะถูกสร้างอัตโนมัติเมื่อ migrate)
```

---

### 5.3 Seeders Data

#### 5.3.1 Branches

```php
$branches = [
    ['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true],
    ['name' => 'ทหารปืนใหญ่', 'code' => '2', 'is_active' => true],
    ['name' => 'ทหารช่าง', 'code' => '3', 'is_active' => true],
    ['name' => 'ทหารสื่อสาร', 'code' => '4', 'is_active' => true],
    ['name' => 'ทหารขนส่ง', 'code' => '5', 'is_active' => true],
];
```

#### 5.3.2 Border Areas

```php
$borderAreas = [
    ['code' => 'BA01', 'name' => 'จ.นราธิวาส', 'special_score' => 5.00, 'is_active' => true],
    ['code' => 'BA02', 'name' => 'จ.ยะลา', 'special_score' => 4.50, 'is_active' => true],
    ['code' => 'BA03', 'name' => 'จ.ปัตตานี', 'special_score' => 4.50, 'is_active' => true],
    ['code' => 'BA04', 'name' => 'จ.สงขลา (บางพื้นที่)', 'special_score' => 3.00, 'is_active' => true],
    ['code' => 'BA05', 'name' => 'จ.เชียงราย (ชายแดน)', 'special_score' => 2.50, 'is_active' => true],
    ['code' => 'BA06', 'name' => 'จ.ตาก (ชายแดน)', 'special_score' => 2.00, 'is_active' => true],
];
```

#### 5.3.3 Test Locations

```php
$testLocations = [
    ['name' => 'กรุงเทพมหานคร', 'code' => '1', 'capacity' => 500, 'is_active' => true],
    ['name' => 'จ.เชียงใหม่', 'code' => '2', 'capacity' => 300, 'is_active' => true],
    ['name' => 'จ.นครราชสีมา', 'code' => '3', 'capacity' => 400, 'is_active' => true],
    ['name' => 'จ.ขอนแก่น', 'code' => '4', 'capacity' => 350, 'is_active' => true],
    ['name' => 'จ.สงขลา', 'code' => '5', 'capacity' => 300, 'is_active' => true],
];
```

#### 5.3.4 Default Staff Account

```php
User::create([
    'national_id' => '1000000000001',
    'rank' => 'จ่าเอก',
    'first_name' => 'Admin',
    'last_name' => 'System',
    'email' => 'admin@exam.military.th',
    'password' => Hash::make('password'),
    'role' => 'staff',
    'is_active' => true,
])->assignRole('staff');
```

---

## 6. Project Structure

```
military-promotion-exam/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Auth/
│   │   │       ├── LoginController.php
│   │   │       ├── RegisterController.php
│   │   │       └── LogoutController.php
│   │   ├── Livewire/
│   │   │   ├── Auth/
│   │   │   │   ├── Login.php
│   │   │   │   ├── Register.php
│   │   │   │   └── StaffRegister.php
│   │   │   ├── Examinee/
│   │   │   │   ├── Dashboard.php
│   │   │   │   ├── ExamRegistration.php
│   │   │   │   ├── Profile.php
│   │   │   │   ├── History.php
│   │   │   │   └── DownloadExamCard.php
│   │   │   ├── Staff/
│   │   │   │   ├── Dashboard.php
│   │   │   │   ├── Examinees/
│   │   │   │   │   ├── Index.php
│   │   │   │   │   ├── Create.php
│   │   │   │   │   ├── Edit.php
│   │   │   │   │   └── AddLate.php
│   │   │   │   ├── BorderAreas/ 🔥
│   │   │   │   │   ├── Index.php
│   │   │   │   │   ├── Create.php
│   │   │   │   │   ├── Edit.php
│   │   │   │   │   └── ScoreHistory.php
│   │   │   │   ├── ExamSessions/
│   │   │   │   ├── TestLocations/
│   │   │   │   ├── Branches/
│   │   │   │   ├── PositionQuotas/
│   │   │   │   ├── ExamNumbers/
│   │   │   │   ├── Reports/
│   │   │   │   └── Users/
│   │   │   └── Commander/
│   │   │       ├── Dashboard.php
│   │   │       └── ViewBorderAreas.php
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php
│   │       └── CheckRegistrationPeriod.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Examinee.php
│   │   ├── ExamSession.php
│   │   ├── ExamRegistration.php
│   │   ├── TestLocation.php
│   │   ├── Branch.php
│   │   ├── BorderArea.php 🔥
│   │   ├── BorderAreaScoreHistory.php 🔥
│   │   ├── ExamineeEditLog.php 🔥
│   │   └── PositionQuota.php
│   ├── Services/
│   │   ├── ExamNumberGenerator.php
│   │   ├── ScoreCalculator.php
│   │   ├── ReportGenerator.php
│   │   ├── BorderAreaService.php 🔥
│   │   └── AuditLogService.php 🔥
│   └── Observers/
│       ├── BorderAreaObserver.php 🔥
│       └── ExamineeObserver.php 🔥
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── RolePermissionSeeder.php
│       ├── BranchSeeder.php
│       ├── BorderAreaSeeder.php
│       ├── TestLocationSeeder.php
│       └── DefaultStaffSeeder.php
├── resources/
│   ├── views/
│   │   ├── components/
│   │   │   └── layouts/
│   │   │       ├── app.blade.php
│   │   │       ├── guest.blade.php
│   │   │       ├── examinee.blade.php
│   │   │       ├── staff.blade.php
│   │   │       └── commander.blade.php
│   │   └── livewire/
│   │       ├── auth/
│   │       ├── examinee/
│   │       ├── staff/
│   │       └── commander/
│   └── css/
│       └── app.css
└── routes/
    └── web.php
```

---

## 7. UI/UX Design Guidelines

### 7.1 TailwindCSS Configuration

```js
// tailwind.config.js
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./app/Livewire/**/*.php",
    ],
    theme: {
        extend: {
            colors: {
                // Primary - Dark Green
                primary: {
                    50: "#f0fdf4",
                    100: "#dcfce7",
                    200: "#bbf7d0",
                    300: "#86efac",
                    400: "#4ade80",
                    500: "#2D6A4F", // Main
                    600: "#1B4332", // Main Dark
                    700: "#14532d",
                    800: "#052e16",
                    900: "#052e16",
                },
                // Secondary - Pastel Yellow
                secondary: {
                    50: "#FFFBEB",
                    100: "#FEF3C7", // Main Light
                    200: "#FDE68A", // Main
                    300: "#FCD34D",
                    400: "#FBBF24",
                    500: "#F59E0B",
                    600: "#D97706",
                    700: "#B45309",
                    800: "#92400E",
                    900: "#78350F",
                },
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
```

### 7.2 Component Examples

#### Login Form

```blade
<div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
    <div class="mb-6 text-center">
        <h2 class="text-3xl font-extrabold text-primary-600">
            เข้าสู่ระบบ
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            ระบบสอบเลื่อนฐานะทหาร
        </p>
    </div>

    <form wire:submit.prevent="login" class="space-y-6">
        <!-- Fields here -->
    </form>
</div>
```

#### Border Area Management Table

```blade
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รหัส</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อพื้นที่</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คะแนนพิเศษ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($borderAreas as $area)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $area->code }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $area->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($area->special_score, 2) }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($area->is_active)
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">เปิด</span>
                    @else
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">ปิด</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <a href="{{ route('staff.border-areas.edit', $area) }}" class="text-primary-600 hover:text-primary-900 mr-4">แก้ไข</a>
                    <button wire:click="delete({{ $area->id }})" class="text-red-600 hover:text-red-900">ลบ</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

---

## 8. Security และ Middleware

### 8.1 Route Protection

```php
// routes/web.php

// Public
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store');
});

// Authenticated
Route::middleware(['auth'])->group(function () {

    // Examinee
    Route::middleware(['role:examinee'])->prefix('examinee')->group(function () {
        Route::get('/dashboard', \App\Livewire\Examinee\Dashboard::class)->name('examinee.dashboard');
        Route::get('/register-exam', \App\Livewire\Examinee\ExamRegistration::class)
            ->middleware('check.registration.period')
            ->name('examinee.register');
        Route::get('/profile', \App\Livewire\Examinee\Profile::class)->name('examinee.profile');
        Route::get('/history', \App\Livewire\Examinee\History::class)->name('examinee.history');
    });

    // Staff
    Route::middleware(['role:staff'])->prefix('staff')->group(function () {
        Route::get('/dashboard', \App\Livewire\Staff\Dashboard::class)->name('staff.dashboard');

        // Border Areas 🔥
        Route::prefix('border-areas')->group(function () {
            Route::get('/', \App\Livewire\Staff\BorderAreas\Index::class)->name('staff.border-areas');
            Route::get('/create', \App\Livewire\Staff\BorderAreas\Create::class)->name('staff.border-areas.create');
            Route::get('/{id}/edit', \App\Livewire\Staff\BorderAreas\Edit::class)->name('staff.border-areas.edit');
            Route::get('/history', \App\Livewire\Staff\BorderAreas\ScoreHistory::class)->name('staff.border-areas.history');
        });

        // ... other routes
    });

    // Commander
    Route::middleware(['role:commander'])->prefix('commander')->group(function () {
        Route::get('/dashboard', \App\Livewire\Commander\Dashboard::class)->name('commander.dashboard');
    });
});
```

### 8.2 Custom Middleware

```php
// app/Http/Middleware/CheckRegistrationPeriod.php
public function handle($request, Closure $next)
{
    $activeSession = ExamSession::where('is_active', true)
        ->whereDate('registration_start', '<=', now())
        ->whereDate('registration_end', '>=', now())
        ->first();

    if (!$activeSession) {
        return redirect()->route('examinee.dashboard')
            ->with('error', 'ไม่อยู่ในช่วงเวลาลงทะเบียน');
    }

    return $next($request);
}
```

---

## 9. Business Logic และ Services

### 9.1 Exam Number Generator

```php
// app/Services/ExamNumberGenerator.php
namespace App\Services;

use App\Models\ExamRegistration;

class ExamNumberGenerator
{
    /**
     * Generate exam numbers: XYZNN
     * X = Test Location Code (1 digit)
     * Y = Branch Code (1 digit)
     * ZNN = Sequence by first_name (3 digits)
     */
    public function generate(int $examSessionId): int
    {
        $registrations = ExamRegistration::where('exam_session_id', $examSessionId)
            ->where('status', 'pending')
            ->with(['examinee.user', 'examinee.branch', 'test_location'])
            ->get();

        $grouped = $registrations->groupBy(function($reg) {
            return $reg->test_location->code . '-' . $reg->examinee->branch->code;
        });

        $count = 0;

        foreach ($grouped as $key => $group) {
            [$locationCode, $branchCode] = explode('-', $key);

            $sorted = $group->sortBy('examinee.user.first_name')->values();

            foreach ($sorted as $index => $registration) {
                $sequence = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                $examNumber = $locationCode . $branchCode . $sequence;

                $registration->update([
                    'exam_number' => $examNumber,
                    'status' => 'confirmed'
                ]);

                $count++;
            }
        }

        return $count;
    }
}
```

### 9.2 Score Calculator

```php
// app/Services/ScoreCalculator.php
namespace App\Services;

class ScoreCalculator
{
    public function calculatePendingScore(int $eligibleYear, int $suspendedYears): float
    {
        $currentYear = now()->year;
        $yearsDiff = $currentYear - $eligibleYear;
        $pendingScore = $yearsDiff - $suspendedYears;

        return max(0, $pendingScore);
    }

    public function calculateTotalScore(float $pendingScore, float $specialScore): float
    {
        return $pendingScore + $specialScore;
    }
}
```

### 9.3 Border Area Service 🔥

```php
// app/Services/BorderAreaService.php
namespace App\Services;

use App\Models\BorderArea;
use App\Models\BorderAreaScoreHistory;

class BorderAreaService
{
    public function updateWithHistory(
        BorderArea $borderArea,
        array $data,
        int $userId,
        ?string $reason = null
    ): BorderArea {
        $oldScore = $borderArea->special_score;
        $newScore = $data['special_score'];

        // Update
        $borderArea->update($data);
        $borderArea->updated_by = $userId;
        $borderArea->save();

        // Log if score changed
        if ($oldScore != $newScore) {
            BorderAreaScoreHistory::create([
                'border_area_id' => $borderArea->id,
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'changed_by' => $userId,
                'reason' => $reason,
                'changed_at' => now(),
            ]);
        }

        return $borderArea;
    }
}
```

---

## 10. API และ Routes

### 10.1 Web Routes Summary

```
Public Routes:
├── GET  /login
├── POST /login
├── GET  /register
└── POST /register

Examinee Routes (Auth + Role:examinee):
├── GET /examinee/dashboard
├── GET /examinee/register-exam (+ CheckRegistrationPeriod)
├── GET /examinee/profile
└── GET /examinee/history

Staff Routes (Auth + Role:staff):
├── GET /staff/dashboard
├── Examinees:
│   ├── GET /staff/examinees
│   ├── GET /staff/examinees/create
│   └── GET /staff/examinees/{id}/edit
├── Border Areas: 🔥
│   ├── GET /staff/border-areas
│   ├── GET /staff/border-areas/create
│   ├── GET /staff/border-areas/{id}/edit
│   └── GET /staff/border-areas/history
├── Exam Sessions:
│   ├── GET /staff/exam-sessions
│   └── GET /staff/exam-sessions/create
├── Reports:
│   └── GET /staff/reports
└── Users:
    └── GET /staff/users/create

Commander Routes (Auth + Role:commander):
├── GET /commander/dashboard
└── GET /commander/reports
```

---

## 11. Testing Strategy

### 11.1 Unit Tests

```php
// tests/Unit/ScoreCalculatorTest.php
public function test_calculate_pending_score()
{
    $calculator = new ScoreCalculator();
    $result = $calculator->calculatePendingScore(2020, 1);

    // (2024 - 2020) - 1 = 3
    $this->assertEquals(3, $result);
}
```

### 11.2 Feature Tests

```php
// tests/Feature/ExamRegistrationTest.php
public function test_examinee_can_register_for_exam()
{
    $user = User::factory()->create(['role' => 'examinee']);
    $this->actingAs($user);

    $response = $this->post('/examinee/register-exam', [/* data */]);

    $response->assertRedirect('/examinee/dashboard');
    $this->assertDatabaseHas('exam_registrations', [
        'examinee_id' => $user->examinee->id,
    ]);
}
```

---

## 12. Deployment และ Configuration

### 12.1 Environment Variables

```env
APP_NAME="ระบบสอบเลื่อนฐานะทหาร"
APP_ENV=production
APP_KEY=base64:xxxxx
APP_DEBUG=false
APP_URL=https://exam.military.th

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=military_exam
DB_USERNAME=root
DB_PASSWORD=xxxxx

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=xxxxx
MAIL_ENCRYPTION=tls
```

### 12.2 Installation Commands

```bash
# 1. Clone & Install
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate:fresh --seed

# 4. Storage
php artisan storage:link

# 5. Build Assets
npm run build

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 12.3 Production Checklist

- ✅ `APP_DEBUG=false`
- ✅ `APP_ENV=production`
- ✅ HTTPS enabled
- ✅ Database backups configured
- ✅ Cron jobs for `schedule:run`
- ✅ Rate limiting configured
- ✅ Error monitoring (Sentry/Bugsnag)
- ✅ Performance monitoring

---

## สรุป

เอกสารนี้จัดทำขึ้นเพื่อให้สามารถนำไปพัฒนาระบบสอบเลื่อนฐานะทหารได้ทันที ครอบคลุม:

1. ✅ **Features ครบถ้วน** - ทุกฟีเจอร์ตามที่กำหนด รวมถึงการจัดการพื้นที่ชายแดน
2. ✅ **RBAC ชัดเจน** - 3 roles พร้อม permission matrix แบบละเอียด
3. ✅ **Tech Stack** - Laravel 11 + Livewire 3 + MySQL 8
4. ✅ **Database Schema** - 15 tables พร้อม relationships และ indexes
5. ✅ **Project Structure** - จัดระเบียบดี พร้อม Services และ Observers
6. ✅ **UI/UX** - พร้อม color theme เขียวเข้ม + เหลือง pastel
7. ✅ **Security** - Middleware, RBAC, Activity Logs
8. ✅ **Business Logic** - Services สำเร็จรูป
9. ✅ **Deployment** - คำแนะนำติดตั้งครบถ้วน

**พร้อมเริ่มพัฒนาได้ทันที!** 🚀

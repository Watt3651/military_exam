<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | ข้อความ Validation ภาษาไทย
    | ระบบสอบเลื่อนฐานะนายทหารประทวน
    |
    */

    'accepted' => 'ต้องยอมรับ :attribute',
    'accepted_if' => 'ต้องยอมรับ :attribute เมื่อ :other เป็น :value',
    'active_url' => ':attribute ไม่ใช่ URL ที่ถูกต้อง',
    'after' => ':attribute ต้องเป็นวันที่หลังจาก :date',
    'after_or_equal' => ':attribute ต้องเป็นวันที่หลังจากหรือเท่ากับ :date',
    'alpha' => ':attribute ต้องประกอบด้วยตัวอักษรเท่านั้น',
    'alpha_dash' => ':attribute ต้องประกอบด้วยตัวอักษร ตัวเลข เครื่องหมายขีดกลาง และขีดล่างเท่านั้น',
    'alpha_num' => ':attribute ต้องประกอบด้วยตัวอักษรและตัวเลขเท่านั้น',
    'any_of' => ':attribute ไม่ถูกต้อง',
    'array' => ':attribute ต้องเป็นอาร์เรย์',
    'ascii' => ':attribute ต้องประกอบด้วยอักขระแบบไบต์เดียวเท่านั้น',
    'before' => ':attribute ต้องเป็นวันที่ก่อน :date',
    'before_or_equal' => ':attribute ต้องเป็นวันที่ก่อนหรือเท่ากับ :date',
    'between' => [
        'array' => ':attribute ต้องมีจำนวนระหว่าง :min ถึง :max รายการ',
        'file' => ':attribute ต้องมีขนาดระหว่าง :min ถึง :max กิโลไบต์',
        'numeric' => ':attribute ต้องมีค่าระหว่าง :min ถึง :max',
        'string' => ':attribute ต้องมีความยาวระหว่าง :min ถึง :max ตัวอักษร',
    ],
    'boolean' => ':attribute ต้องเป็นจริงหรือเท็จ',
    'can' => ':attribute มีค่าที่ไม่ได้รับอนุญาต',
    'confirmed' => 'การยืนยัน :attribute ไม่ตรงกัน',
    'contains' => ':attribute ขาดค่าที่จำเป็น',
    'current_password' => 'รหัสผ่านไม่ถูกต้อง',
    'date' => ':attribute ไม่ใช่วันที่ที่ถูกต้อง',
    'date_equals' => ':attribute ต้องเป็นวันที่เท่ากับ :date',
    'date_format' => ':attribute ไม่ตรงกับรูปแบบ :format',
    'decimal' => ':attribute ต้องมี :decimal ตำแหน่งทศนิยม',
    'declined' => ':attribute ต้องถูกปฏิเสธ',
    'declined_if' => ':attribute ต้องถูกปฏิเสธเมื่อ :other เป็น :value',
    'different' => ':attribute และ :other ต้องแตกต่างกัน',
    'digits' => ':attribute ต้องเป็นตัวเลข :digits หลัก',
    'digits_between' => ':attribute ต้องมีความยาวระหว่าง :min ถึง :max หลัก',
    'dimensions' => ':attribute มีขนาดรูปภาพไม่ถูกต้อง',
    'distinct' => ':attribute มีค่าที่ซ้ำกัน',
    'doesnt_contain' => ':attribute ต้องไม่ประกอบด้วย: :values',
    'doesnt_end_with' => ':attribute ต้องไม่ลงท้ายด้วย: :values',
    'doesnt_start_with' => ':attribute ต้องไม่ขึ้นต้นด้วย: :values',
    'email' => ':attribute ต้องเป็นอีเมลที่ถูกต้อง',
    'encoding' => ':attribute ต้องถูกเข้ารหัสเป็น :encoding',
    'ends_with' => ':attribute ต้องลงท้ายด้วย: :values',
    'enum' => ':attribute ที่เลือกไม่ถูกต้อง',
    'exists' => ':attribute ที่เลือกไม่ถูกต้อง',
    'extensions' => ':attribute ต้องมีนามสกุลไฟล์: :values',
    'file' => ':attribute ต้องเป็นไฟล์',
    'filled' => ':attribute ต้องมีค่า',
    'gt' => [
        'array' => ':attribute ต้องมีมากกว่า :value รายการ',
        'file' => ':attribute ต้องมีขนาดมากกว่า :value กิโลไบต์',
        'numeric' => ':attribute ต้องมีค่ามากกว่า :value',
        'string' => ':attribute ต้องมีความยาวมากกว่า :value ตัวอักษร',
    ],
    'gte' => [
        'array' => ':attribute ต้องมีอย่างน้อย :value รายการ',
        'file' => ':attribute ต้องมีขนาดอย่างน้อย :value กิโลไบต์',
        'numeric' => ':attribute ต้องมีค่าอย่างน้อย :value',
        'string' => ':attribute ต้องมีความยาวอย่างน้อย :value ตัวอักษร',
    ],
    'hex_color' => ':attribute ต้องเป็นรหัสสีฐานสิบหกที่ถูกต้อง',
    'image' => ':attribute ต้องเป็นรูปภาพ',
    'in' => ':attribute ที่เลือกไม่ถูกต้อง',
    'in_array' => ':attribute ต้องมีอยู่ใน :other',
    'in_array_keys' => ':attribute ต้องมี key อย่างน้อย: :values',
    'integer' => ':attribute ต้องเป็นจำนวนเต็ม',
    'ip' => ':attribute ต้องเป็น IP address ที่ถูกต้อง',
    'ipv4' => ':attribute ต้องเป็น IPv4 address ที่ถูกต้อง',
    'ipv6' => ':attribute ต้องเป็น IPv6 address ที่ถูกต้อง',
    'json' => ':attribute ต้องเป็น JSON string ที่ถูกต้อง',
    'list' => ':attribute ต้องเป็นรายการ',
    'lowercase' => ':attribute ต้องเป็นตัวพิมพ์เล็ก',
    'lt' => [
        'array' => ':attribute ต้องมีน้อยกว่า :value รายการ',
        'file' => ':attribute ต้องมีขนาดน้อยกว่า :value กิโลไบต์',
        'numeric' => ':attribute ต้องมีค่าน้อยกว่า :value',
        'string' => ':attribute ต้องมีความยาวน้อยกว่า :value ตัวอักษร',
    ],
    'lte' => [
        'array' => ':attribute ต้องไม่มีมากกว่า :value รายการ',
        'file' => ':attribute ต้องมีขนาดไม่เกิน :value กิโลไบต์',
        'numeric' => ':attribute ต้องมีค่าไม่เกิน :value',
        'string' => ':attribute ต้องมีความยาวไม่เกิน :value ตัวอักษร',
    ],
    'mac_address' => ':attribute ต้องเป็น MAC address ที่ถูกต้อง',
    'max' => [
        'array' => ':attribute ต้องไม่มีมากกว่า :max รายการ',
        'file' => ':attribute ต้องมีขนาดไม่เกิน :max กิโลไบต์',
        'numeric' => ':attribute ต้องไม่มากกว่า :max',
        'string' => ':attribute ต้องมีความยาวไม่เกิน :max ตัวอักษร',
    ],
    'max_digits' => ':attribute ต้องไม่เกิน :max หลัก',
    'mimes' => ':attribute ต้องเป็นไฟล์ประเภท: :values',
    'mimetypes' => ':attribute ต้องเป็นไฟล์ประเภท: :values',
    'min' => [
        'array' => ':attribute ต้องมีอย่างน้อย :min รายการ',
        'file' => ':attribute ต้องมีขนาดอย่างน้อย :min กิโลไบต์',
        'numeric' => ':attribute ต้องมีค่าอย่างน้อย :min',
        'string' => ':attribute ต้องมีความยาวอย่างน้อย :min ตัวอักษร',
    ],
    'min_digits' => ':attribute ต้องมีอย่างน้อย :min หลัก',
    'missing' => ':attribute ต้องไม่มีอยู่',
    'missing_if' => ':attribute ต้องไม่มีอยู่เมื่อ :other เป็น :value',
    'missing_unless' => ':attribute ต้องไม่มีอยู่เว้นแต่ :other เป็น :value',
    'missing_with' => ':attribute ต้องไม่มีอยู่เมื่อมี :values',
    'missing_with_all' => ':attribute ต้องไม่มีอยู่เมื่อมี :values ทั้งหมด',
    'multiple_of' => ':attribute ต้องเป็นจำนวนทวีคูณของ :value',
    'not_in' => ':attribute ที่เลือกไม่ถูกต้อง',
    'not_regex' => 'รูปแบบ :attribute ไม่ถูกต้อง',
    'numeric' => ':attribute ต้องเป็นตัวเลข',
    'password' => [
        'letters' => ':attribute ต้องมีตัวอักษรอย่างน้อย 1 ตัว',
        'mixed' => ':attribute ต้องมีตัวอักษรพิมพ์ใหญ่และพิมพ์เล็กอย่างน้อยอย่างละ 1 ตัว',
        'numbers' => ':attribute ต้องมีตัวเลขอย่างน้อย 1 ตัว',
        'symbols' => ':attribute ต้องมีอักขระพิเศษอย่างน้อย 1 ตัว',
        'uncompromised' => ':attribute ที่ระบุปรากฏในข้อมูลรั่วไหล กรุณาเลือก :attribute อื่น',
    ],
    'present' => ':attribute ต้องมีอยู่',
    'present_if' => ':attribute ต้องมีอยู่เมื่อ :other เป็น :value',
    'present_unless' => ':attribute ต้องมีอยู่เว้นแต่ :other เป็น :value',
    'present_with' => ':attribute ต้องมีอยู่เมื่อมี :values',
    'present_with_all' => ':attribute ต้องมีอยู่เมื่อมี :values ทั้งหมด',
    'prohibited' => ':attribute ไม่อนุญาตให้กรอก',
    'prohibited_if' => ':attribute ไม่อนุญาตให้กรอกเมื่อ :other เป็น :value',
    'prohibited_if_accepted' => ':attribute ไม่อนุญาตให้กรอกเมื่อ :other ถูกยอมรับ',
    'prohibited_if_declined' => ':attribute ไม่อนุญาตให้กรอกเมื่อ :other ถูกปฏิเสธ',
    'prohibited_unless' => ':attribute ไม่อนุญาตให้กรอกเว้นแต่ :other อยู่ใน :values',
    'prohibits' => ':attribute ไม่อนุญาตให้มี :other',
    'regex' => 'รูปแบบ :attribute ไม่ถูกต้อง',
    'required' => 'กรุณากรอก :attribute',
    'required_array_keys' => ':attribute ต้องมีรายการสำหรับ: :values',
    'required_if' => 'กรุณากรอก :attribute เมื่อ :other เป็น :value',
    'required_if_accepted' => 'กรุณากรอก :attribute เมื่อ :other ถูกยอมรับ',
    'required_if_declined' => 'กรุณากรอก :attribute เมื่อ :other ถูกปฏิเสธ',
    'required_unless' => 'กรุณากรอก :attribute เว้นแต่ :other อยู่ใน :values',
    'required_with' => 'กรุณากรอก :attribute เมื่อมี :values',
    'required_with_all' => 'กรุณากรอก :attribute เมื่อมี :values ทั้งหมด',
    'required_without' => 'กรุณากรอก :attribute เมื่อไม่มี :values',
    'required_without_all' => 'กรุณากรอก :attribute เมื่อไม่มี :values ทั้งหมด',
    'same' => ':attribute และ :other ต้องตรงกัน',
    'size' => [
        'array' => ':attribute ต้องมี :size รายการ',
        'file' => ':attribute ต้องมีขนาด :size กิโลไบต์',
        'numeric' => ':attribute ต้องเท่ากับ :size',
        'string' => ':attribute ต้องมีความยาว :size ตัวอักษร',
    ],
    'starts_with' => ':attribute ต้องขึ้นต้นด้วย: :values',
    'string' => ':attribute ต้องเป็นข้อความ',
    'timezone' => ':attribute ต้องเป็นเขตเวลาที่ถูกต้อง',
    'unique' => ':attribute นี้ถูกใช้งานแล้ว',
    'uploaded' => 'การอัปโหลด :attribute ล้มเหลว',
    'uppercase' => ':attribute ต้องเป็นตัวพิมพ์ใหญ่',
    'url' => ':attribute ต้องเป็น URL ที่ถูกต้อง',
    'ulid' => ':attribute ต้องเป็น ULID ที่ถูกต้อง',
    'uuid' => ':attribute ต้องเป็น UUID ที่ถูกต้อง',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | ข้อความ validation เฉพาะสำหรับระบบสอบเลื่อนฐานะ
    |
    */

    'custom' => [
        'military_id' => [
            'required' => 'กรุณากรอกหมายเลขประจำตัวทหาร',
            'unique' => 'หมายเลขประจำตัวทหารนี้มีอยู่ในระบบแล้ว',
            'regex' => 'รูปแบบหมายเลขประจำตัวทหารไม่ถูกต้อง',
        ],
        'exam_number' => [
            'required' => 'กรุณากรอกหมายเลขสอบ',
            'unique' => 'หมายเลขสอบนี้ถูกใช้งานแล้ว',
        ],
        'national_id' => [
            'required' => 'กรุณากรอกเลขบัตรประชาชน',
            'digits' => 'เลขบัตรประชาชนต้องเป็นตัวเลข 13 หลัก',
            'unique' => 'เลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว',
        ],
        'score' => [
            'required' => 'กรุณากรอกคะแนน',
            'numeric' => 'คะแนนต้องเป็นตัวเลข',
            'min' => 'คะแนนต้องไม่น้อยกว่า :min',
            'max' => 'คะแนนต้องไม่เกิน :max',
        ],
        'registration_start' => [
            'required' => 'กรุณาระบุวันเริ่มรับสมัคร',
            'before' => 'วันเริ่มรับสมัครต้องก่อนวันปิดรับสมัคร',
        ],
        'registration_end' => [
            'required' => 'กรุณาระบุวันปิดรับสมัคร',
            'after' => 'วันปิดรับสมัครต้องหลังวันเริ่มรับสมัคร',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | แปลชื่อ attribute ให้เป็นภาษาไทยที่เข้าใจง่าย
    | เมื่อ validation error แสดง จะใช้ชื่อภาษาไทยแทนชื่อ field
    |
    */

    'attributes' => [
        // ── ข้อมูลผู้ใช้ ──
        'name' => 'ชื่อ',
        'first_name' => 'ชื่อ',
        'last_name' => 'นามสกุล',
        'email' => 'อีเมล',
        'password' => 'รหัสผ่าน',
        'password_confirmation' => 'ยืนยันรหัสผ่าน',
        'username' => 'ชื่อผู้ใช้',
        'phone' => 'เบอร์โทรศัพท์',

        // ── ข้อมูลทหาร ──
        'military_id' => 'หมายเลขประจำตัวทหาร',
        'national_id' => 'เลขบัตรประชาชน',
        'rank' => 'ยศ',
        'position' => 'ตำแหน่ง',
        'branch_id' => 'เหล่า',
        'age' => 'อายุ',
        'unit' => 'หน่วย',
        'affiliation' => 'สังกัด',

        // ── ข้อมูลการสอบ ──
        'exam_number' => 'หมายเลขสอบ',
        'exam_session_id' => 'รอบสอบ',
        'exam_year' => 'ปีการสอบ',
        'exam_level' => 'ระดับการสอบ',
        'test_location_id' => 'สถานที่สอบ',
        'score' => 'คะแนน',
        'total_score' => 'คะแนนรวม',
        'seniority_score' => 'คะแนนค้างบรรจุ',
        'special_score' => 'คะแนนพิเศษ',
        'border_area_id' => 'พื้นที่ชายแดน',

        // ── รอบสอบ ──
        'registration_start' => 'วันเริ่มรับสมัคร',
        'registration_end' => 'วันปิดรับสมัคร',
        'exam_date' => 'วันสอบ',
        'announcement_date' => 'วันประกาศผล',
        'academic_year' => 'ปีการศึกษา',

        // ── ข้อมูลทั่วไป ──
        'status' => 'สถานะ',
        'reason' => 'เหตุผล',
        'description' => 'คำอธิบาย',
        'note' => 'หมายเหตุ',
        'file' => 'ไฟล์',
        'image' => 'รูปภาพ',
        'date' => 'วันที่',
        'time' => 'เวลา',
        'quota' => 'โควตา',
        'amount' => 'จำนวน',
    ],

];

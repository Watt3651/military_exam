<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            color: #111827;
        }
        .card {
            width: 100%;
            border: 2px solid #111827;
            border-radius: 10px;
            padding: 18px;
            box-sizing: border-box;
        }
        .header {
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .subtitle {
            font-size: 12px;
            color: #374151;
            margin-top: 4px;
        }
        .exam-number-label {
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 4px;
        }
        .exam-number {
            font-size: 56px;
            font-weight: bold;
            letter-spacing: 3px;
            line-height: 1;
            margin: 0 0 12px 0;
        }
        .row {
            margin: 4px 0;
            font-size: 14px;
        }
        .row strong {
            display: inline-block;
            width: 110px;
        }
        .bottom {
            margin-top: 16px;
            border-top: 1px dashed #d1d5db;
            padding-top: 12px;
        }
        .qr-box {
            margin-top: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px;
            font-size: 11px;
            color: #374151;
            word-break: break-all;
        }
        .hint {
            margin-top: 10px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <p class="title">บัตรประจำตัวสอบ</p>
            <p class="subtitle">ระบบสอบเลื่อนฐานะทหารประทวน</p>
        </div>

        <div>
            <div class="exam-number-label">หมายเลขสอบ</div>
            <p class="exam-number">{{ $examNumber }}</p>
        </div>

        <div class="row"><strong>ชื่อ-นามสกุล:</strong> {{ $fullName }}</div>
        <div class="row"><strong>เลขประจำตัว:</strong> {{ $nationalId }}</div>
        <div class="row"><strong>สถานที่สอบ:</strong> {{ $testLocation }}</div>
        <div class="row"><strong>วันที่สอบ:</strong> {{ $examDate }}</div>
        <div class="row"><strong>ระดับ:</strong> {{ $examLevel }} (ปี {{ $sessionYear }})</div>

        <div class="bottom">
            @if (!empty($showQr) && !empty($qrPayload))
                <div class="row"><strong>QR Code:</strong> (optional)</div>
                <div class="qr-box">
                    {{ $qrPayload }}
                </div>
            @endif

            <div class="hint">
                กรุณานำบัตรนี้พร้อมบัตรประจำตัวประชาชนมาในวันสอบ
            </div>
        </div>
    </div>
</body>
</html>

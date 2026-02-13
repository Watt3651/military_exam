<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 12px; }
        .meta { margin-bottom: 10px; }
        .meta div { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; }
        .text-right { text-align: right; }
        .footer { margin-top: 14px; font-size: 11px; color: #374151; }
    </style>
</head>
<body>
    <div class="title">รายชื่อผู้สอบ (แยกตามสถานที่สอบ)</div>

    <div class="meta">
        <div><strong>สถานที่สอบ:</strong> {{ $locationName }}</div>
        <div><strong>วันที่สอบ:</strong> {{ $examDate }}</div>
        <div><strong>ระดับการสอบ:</strong> {{ $examLevelLabel }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>หมายเลขสอบ</th>
                <th>ยศ ชื่อ-นามสกุล</th>
                <th>หมายเลขประจำตัว</th>
                <th>เหล่า</th>
                <th>คะแนนรวม</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['exam_number'] }}</td>
                    <td>{{ $row['full_name'] }}</td>
                    <td>{{ $row['national_id'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td class="text-right">{{ $row['total_score'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">ไม่พบข้อมูลผู้เข้าสอบตามเงื่อนไขที่เลือก</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div><strong>จำนวนผู้สอบทั้งหมด:</strong> {{ count($rows) }}</div>
        <div><strong>วันที่พิมพ์รายงาน:</strong> {{ $printedAt }}</div>
        <div><strong>ผู้พิมพ์รายงาน:</strong> {{ $printedBy }}</div>
    </div>
</body>
</html>

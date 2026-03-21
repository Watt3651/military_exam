<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100 p-6">
            <h2 class="text-xl font-semibold text-gray-900">ดาวน์โหลดบัตรประจำตัวสอบ</h2>

            @if ($errorMessage)
                <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-4 text-amber-800 text-sm">
                    {{ $errorMessage }}
                </div>
            @elseif ($registration)
                <div class="mt-4 space-y-2 text-sm text-gray-700">
                    <p><span class="font-medium">หมายเลขสอบ:</span> {{ $registration->exam_number }}</p>
                    <p><span class="font-medium">สถานที่สอบ:</span> {{ $registration->testLocation?->name ?? '-' }}</p>
                    <p><span class="font-medium">วันที่สอบ:</span> {{ $registration->examSession?->exam_date?->format('d/m/Y') ?? '-' }}</p>
                </div>

                <div class="mt-6">
                    <a href="{{ route('examinee.download-pdf') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-green-100">
                        ดาวน์โหลดบัตรประจำตัวสอบ (PDF)
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

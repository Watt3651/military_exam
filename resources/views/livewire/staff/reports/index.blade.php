<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900">รายงาน (Section 2.7)</h2>
                <p class="mt-1 text-sm text-gray-500">เลือกประเภทรายงาน ตั้งค่า filters และดาวน์โหลดไฟล์ PDF/Excel</p>
            </div>

            <form wire:submit="generate" class="p-6 space-y-6">
                <div>
                    <label for="reportType" class="block text-sm font-medium text-gray-700">ประเภทรายงาน</label>
                    <select id="reportType" wire:model.live="reportType"
                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        <option value="examinee_list_pdf">พิมพ์รายชื่อผู้สอบ (PDF)</option>
                        <option value="all_examinees_excel">Export ข้อมูลผู้สมัครทั้งหมด (Excel)</option>
                    </select>
                    @error('reportType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="exam_session_id" class="block text-sm font-medium text-gray-700">รอบสอบ</label>
                        <select id="exam_session_id" wire:model.live="exam_session_id"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="">ทั้งหมด</option>
                            @foreach ($this->examSessions as $session)
                                <option value="{{ $session->id }}">{{ $session->display_name }}</option>
                            @endforeach
                        </select>
                        @error('exam_session_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="exam_level" class="block text-sm font-medium text-gray-700">ระดับการสอบ</label>
                        <select id="exam_level" wire:model.live="exam_level"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="">ทั้งหมด</option>
                            <option value="sergeant_major">จ่าเอก</option>
                            <option value="master_sergeant">พันจ่าเอก</option>
                        </select>
                        @error('exam_level') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="branch_id" class="block text-sm font-medium text-gray-700">เหล่า</label>
                        <select id="branch_id" wire:model.live="branch_id"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                            <option value="">ทั้งหมด</option>
                            @foreach ($this->branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                            @endforeach
                        </select>
                        @error('branch_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="test_location_id" class="block text-sm font-medium text-gray-700">
                            สถานที่สอบ
                            @if ($reportType === 'examinee_list_pdf')
                                <span class="text-red-600">*</span>
                            @endif
                        </label>
                        <select id="test_location_id" wire:model.live="test_location_id"
                            class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                            @disabled($reportType !== 'examinee_list_pdf')>
                            <option value="">-- เลือกสถานที่สอบ --</option>
                            @foreach ($this->testLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }} ({{ $location->code }})</option>
                            @endforeach
                        </select>
                        @error('test_location_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="generate">
                            {{ $reportType === 'all_examinees_excel' ? 'Generate Excel' : 'Generate PDF' }}
                        </span>
                        <span wire:loading wire:target="generate">กำลังสร้างไฟล์...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
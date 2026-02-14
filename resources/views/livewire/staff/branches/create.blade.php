<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-100">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">เพิ่มเหล่า</h2>
                    <p class="mt-1 text-sm text-gray-500">กรอกข้อมูลเหล่าใหม่ตาม Section 2.3.4</p>
                </div>
                <a href="{{ route('staff.branches.index') }}" wire:navigate
                    class="text-sm text-primary-600 hover:text-primary-700">กลับไปรายการ</a>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">ชื่อเหล่า</label>
                    <input id="name" type="text" wire:model.live="name"
                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="เช่น ทหารราบ">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">รหัสเหล่า (1 หลัก: 1-9)</label>
                    <input id="code" type="text" inputmode="numeric" maxlength="1" wire:model.live="code"
                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        placeholder="เช่น 1">
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" wire:model.live="is_active"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-700">เปิดใช้งาน</span>
                    </label>
                    @error('is_active') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <a href="{{ route('staff.branches.index') }}" wire:navigate
                        class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        ยกเลิก
                    </a>
                    <button type="submit"
                        class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">บันทึก</span>
                        <span wire:loading wire:target="save">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
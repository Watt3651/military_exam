<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">สร้างสังกัดใหม่</h2>
                <p class="mt-1 text-sm text-gray-500">กรอกข้อมูลสังกัดหน่วยทหาร</p>
            </div>

            <form wire:submit="save" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="name" value="ชื่อสังกัด" />
                        <x-text-input id="name" wire:model="name" type="text" class="block mt-1 w-full" placeholder="เช่น กรมทหารราบที่ 1" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="code" value="รหัสสังกัด" />
                        <x-text-input id="code" wire:model="code" type="text" class="block mt-1 w-full" placeholder="เช่น รบ.1" />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <input id="is_active" wire:model="is_active" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            เปิดใช้งาน
                        </label>
                    </div>
                </div>

                <div>
                    <x-input-label for="description" value="รายละเอียดเพิ่มเติม" />
                    <textarea id="description" wire:model="description" rows="3"
                              class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                              placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับสังกัดนี้..."></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('staff.units.index') }}" wire:navigate
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        ยกเลิก
                    </a>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <span wire:loading.remove wire:target="save">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="save">บันทึก</span>
                        <span wire:loading wire:target="save">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

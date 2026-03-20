<div>
    @if ($showAlert && $unreadNotifications->isNotEmpty())
        {{-- Notification Alert Container --}}
        <div class="fixed top-4 right-4 z-50 max-w-md">
            @foreach ($unreadNotifications as $notification)
                <div class="mb-3 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out"
                     x-data="{ show: true }"
                     x-show="show"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-x-full"
                     x-transition:enter-end="opacity-100 transform translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform translate-x-0"
                     x-transition:leave-end="opacity-0 transform translate-x-full">
                    
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-yellow-800">
                                📢 แจ้งเตือนการตรวจสอบข้อมูล
                            </p>
                            <p class="mt-1 text-sm text-yellow-700">
                                {{ $notification->data['message'] ?? 'กรุณาตรวจสอบข้อมูลของคุณ' }}
                            </p>
                            <p class="mt-1 text-xs text-yellow-600">
                                โดย: {{ $notification->data['staff_name'] ?? 'เจ้าหน้าที่' }} • {{ $notification->data['created_at'] ?? '' }}
                            </p>
                            <div class="mt-3 flex space-x-2">
                                <button onclick="window.location.href='{{ $notification->data['url'] ?? route('examinee.profile') }}'"
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                    แก้ไขข้อมูล
                                </button>
                                <button wire:click="markAsRead('{{ $notification->id }}')"
                                        class="inline-flex items-center px-3 py-1.5 border border-yellow-300 text-xs font-medium rounded-md text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                    ปิด
                                </button>
                            </div>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button wire:click="markAsRead('{{ $notification->id }}')"
                                        class="inline-flex rounded-md p-1.5 text-yellow-400 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600">
                                    <span class="sr-only">ปิด</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Auto-close after 10 seconds --}}
    <script>
        document.addEventListener('livewire:init', () => {
            @if ($showAlert)
                setTimeout(() => {
                    @this.closeAlert();
                }, 10000);
            @endif
        });
    </script>
</div>

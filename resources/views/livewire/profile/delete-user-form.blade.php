<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            ลบบัญชี
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            เมื่อบัญชีของคุณถูกลบ ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณาดาวน์โหลดข้อมูลที่คุณต้องการเก็บไว้ก่อนทำการลบบัญชี
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >ลบบัญชี</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-medium text-gray-900">
                คุณแน่ใจหรือว่าต้องการลบบัญชีของคุณ?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                เมื่อบัญชีของคุณถูกลบ ข้อมูลและทรัพยากรทั้งหมดจะถูกลบอย่างถาวร กรุณากรอกรหัสผ่านของคุณเพื่อยืนยันว่าคุณต้องการลบบัญชีอย่างถาวร
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="รหัสผ่าน" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="รหัสผ่าน"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    ยกเลิก
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    ลบบัญชี
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

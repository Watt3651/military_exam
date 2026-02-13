<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $national_id = '';
    public string $rank = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->national_id = $user->national_id;
        $this->rank = $user->rank;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'rank' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->full_name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            ข้อมูลส่วนตัว
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            แก้ไขข้อมูลโปรไฟล์และอีเมล (ถ้ามี)
        </p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-6">
        <!-- National ID (Read-only) -->
        <div>
            <x-input-label for="national_id" value="หมายเลขประจำตัว 13 หลัก" />
            <x-text-input 
                wire:model="national_id" 
                id="national_id" 
                name="national_id" 
                type="text" 
                class="mt-1 block w-full bg-gray-100" 
                disabled 
                readonly />
            <p class="mt-1 text-xs text-gray-500">หมายเลขประจำตัวไม่สามารถแก้ไขได้</p>
        </div>

        <!-- Rank -->
        <div>
            <x-input-label for="rank" value="ยศ" />
            <x-text-input 
                wire:model="rank" 
                id="rank" 
                name="rank" 
                type="text" 
                class="mt-1 block w-full" 
                required 
                autofocus 
                placeholder="เช่น พ.อ., ร.ต., ร.อ." />
            <x-input-error class="mt-2" :messages="$errors->get('rank')" />
        </div>

        <!-- First Name -->
        <div>
            <x-input-label for="first_name" value="ชื่อ" />
            <x-text-input 
                wire:model="first_name" 
                id="first_name" 
                name="first_name" 
                type="text" 
                class="mt-1 block w-full" 
                required
                placeholder="กรอกชื่อ" />
            <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
        </div>

        <!-- Last Name -->
        <div>
            <x-input-label for="last_name" value="นามสกุล" />
            <x-text-input 
                wire:model="last_name" 
                id="last_name" 
                name="last_name" 
                type="text" 
                class="mt-1 block w-full" 
                required
                placeholder="กรอกนามสกุล" />
            <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
        </div>

        <!-- Email (Optional) -->
        <div>
            <x-input-label for="email" value="อีเมล (ไม่บังคับ)" />
            <x-text-input 
                wire:model="email" 
                id="email" 
                name="email" 
                type="email" 
                class="mt-1 block w-full" 
                autocomplete="username"
                placeholder="กรอกอีเมล (ถ้ามี)" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if (auth()->user()->email && auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-800">
                        อีเมลของคุณยังไม่ได้รับการยืนยัน

                        <button wire:click.prevent="sendVerification" class="underline text-sm text-primary-600 hover:text-primary-700 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            คลิกที่นี่เพื่อส่งอีเมลยืนยันอีกครั้ง
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            ลิงก์ยืนยันใหม่ถูกส่งไปยังอีเมลของคุณแล้ว
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>บันทึก</x-primary-button>

            <x-action-message class="me-3" on="profile-updated">
                บันทึกสำเร็จ
            </x-action-message>
        </div>
    </form>
</section>

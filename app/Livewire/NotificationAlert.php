<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\User;

/**
 * NotificationAlert Component
 * 
 * แสดงแจ้งเตือนให้ผู้สมัครตรวจสอบข้อมูล
 */
class NotificationAlert extends Component
{
    /** @var Collection<int, \Illuminate\Notifications\DatabaseNotification> */
    public Collection $unreadNotifications;

    /** @var bool */
    public $showAlert = false;

    public function mount(): void
    {
        $this->loadNotifications();
    }

    /**
     * โหลด notifications ที่ยังไม่ได้อ่าน
     */
    private function loadNotifications(): void
    {
        if (!Auth::check()) {
            $this->unreadNotifications = collect();
            $this->showAlert = false;
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        
        \Log::info('Loading notifications for user', [
            'user_id' => $user->id,
            'user_role' => $user->role,
        ]);
        
        $this->unreadNotifications = $user->notifications()
            ->whereNull('read_at')
            ->where('type', 'App\\Notifications\\DataReviewNotification')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->showAlert = $this->unreadNotifications->isNotEmpty();
        
        \Log::info('Notifications loaded', [
            'count' => $this->unreadNotifications->count(),
            'show_alert' => $this->showAlert,
        ]);
    }

    /**
     * ปิด alert
     */
    public function closeAlert(): void
    {
        $this->showAlert = false;
    }

    /**
     * Mark notification ว่าอ่านแล้ว
     */
    public function markAsRead(string $notificationId): void
    {
        if (!Auth::check()) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    /**
     * Mark ทั้งหมดว่าอ่านแล้ว
     */
    public function markAllAsRead(): void
    {
        if (!Auth::check()) {
            return;
        }

        /** @var User $user */
        $user = Auth::user();
        
        $user->notifications()
            ->whereNull('read_at')
            ->where('type', 'App\\Notifications\\DataReviewNotification')
            ->update(['read_at' => now()]);
            
        $this->loadNotifications();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.notification-alert');
    }
}

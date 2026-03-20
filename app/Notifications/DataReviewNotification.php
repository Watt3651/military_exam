<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

/**
 * Data Review Notification
 * 
 * แจ้งเตือนผู้สมัครให้ตรวจสอบข้อมูล
 */
class DataReviewNotification extends Notification
{
    use Queueable;

    /**
     * @param string $message ข้อความแจ้งเตือน
     * @param \App\Models\User $staff Staff ที่ส่งแจ้งเตือน
     */
    public function __construct(
        private string $message,
        private \App\Models\User $staff
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database']; // เก็บในฐานข้อมูลเท่านั้น
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'message' => $this->message,
            'staff_name' => $this->staff->full_name,
            'url' => route('examinee.profile'),
            'created_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'staff_name' => $this->staff->full_name,
            'url' => route('examinee.profile'),
            'created_at' => now()->format('d/m/Y H:i'),
        ];
    }
}

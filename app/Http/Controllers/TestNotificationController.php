<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Examinee;
use App\Notifications\DataReviewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestNotificationController extends Controller
{
    /**
     * Test notification system
     */
    public function test()
    {
        try {
            // หา user และ examinee แรก
            $user = User::where('role', 'examinee')->first();
            $examinee = Examinee::first();
            $staff = User::where('role', 'staff')->first();

            if (!$user || !$examinee || !$staff) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Missing required data',
                    'data' => [
                        'user_exists' => $user ? true : false,
                        'examinee_exists' => $examinee ? true : false,
                        'staff_exists' => $staff ? true : false,
                    ]
                ]);
            }

            // ส่ง notification
            $user->notify(
                new DataReviewNotification('ทดสอบการส่งแจ้งเตือน', $staff)
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Notification sent successfully',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'examinee_id' => $examinee->id,
                    'staff_name' => $staff->full_name,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Test notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check notifications for user
     */
    public function check(Request $request)
    {
        $userId = $request->get('user_id', 1);
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found']);
        }

        $notifications = $user->notifications()
            ->whereNull('read_at')
            ->where('type', 'App\\Notifications\\DataReviewNotification')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user_id' => $userId,
            'user_name' => $user->full_name,
            'user_role' => $user->role,
            'unread_count' => $notifications->count(),
            'notifications' => $notifications->map(function ($n) {
                return [
                    'id' => $n->id,
                    'message' => $n->data['message'] ?? '',
                    'staff_name' => $n->data['staff_name'] ?? '',
                    'created_at' => $n->created_at,
                ];
            })
        ]);
    }
}

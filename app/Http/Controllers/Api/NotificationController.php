<?php

namespace App\Http\Controllers\Api;

use App\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->when($request->boolean('unread_only'), fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            'Notifications retrieved successfully.',
            [
                'notifications' => $notifications->map(fn ($notification) => [
                    'id' => $notification->id,
                    'type' => $notification->data['type'] ?? $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at?->toDateTimeString(),
                    'created_at' => $notification->created_at?->toDateTimeString(),
                ]),
            ],
        );
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->markAsRead();

        return $this->success('Notification marked as read.', [
            'notification' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toDateTimeString(),
            ],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success('All notifications marked as read.', []);
    }
}

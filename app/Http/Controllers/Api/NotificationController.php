<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $request->user()->notifications()->latest()->paginate(15)
        );
    }

    public function unread(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $request->user()->unreadNotifications()->latest()->paginate(15)
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function show(Request $request, string $notification): NotificationResource
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        return new NotificationResource($notification);
    }

    public function markAsRead(Request $request, string $notification): NotificationResource
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notification->markAsRead();

        return new NotificationResource($notification->refresh());
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail()
            ->delete();

        return response()->json([
            'message' => 'Notification deleted successfully.',
        ]);
    }

    public function destroyRead(Request $request): JsonResponse
    {
        $request->user()->readNotifications()->delete();

        return response()->json([
            'message' => 'Read notifications deleted successfully.',
        ]);
    }
}

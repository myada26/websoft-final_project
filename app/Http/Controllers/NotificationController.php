<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = (int) $request->integer('per_page', 5);
        $perPage = max(1, min($perPage, 50));

        $unread = $user->unreadNotifications()
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'unread_page');

        $read = $user->readNotifications()
            ->orderByDesc('read_at')
            ->paginate($perPage, ['*'], 'read_page');

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'unread'       => $this->formatPage($unread),
            'read'         => $this->formatPage($read),
        ]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'id'      => $notification->id,
            'read_at' => $notification->read_at?->toIso8601String(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['marked' => $count]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->delete();

        return response()->json(['deleted' => $id]);
    }

    private function formatPage(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type']        ?? 'general',
                'title'      => $n->data['title']       ?? 'Notification',
                'message'    => $n->data['message']     ?? '',
                'tone'       => $n->data['tone']        ?? 'gray',
                'icon'       => $n->data['icon']        ?? 'bell',
                'action_url' => $n->data['action_url']  ?? null,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
                'time_human' => $n->created_at->diffForHumans(),
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }
}

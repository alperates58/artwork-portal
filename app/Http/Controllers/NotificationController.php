<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $this->notifications->latest($user, 15);
        $unread = $items->whereNull('read_at')->count();

        return response()->json([
            'unread' => $unread,
            'items'  => $items->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'url'        => $n->url,
                'read'       => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(Request $request): JsonResponse|RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Tüm bildirimler okundu olarak işaretlendi.');
    }
}

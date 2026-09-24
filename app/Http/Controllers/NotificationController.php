<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(20)->through(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notifikasi',
                'message' => $notification->data['message'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'level' => $notification->data['level'] ?? 'info',
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ]),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}

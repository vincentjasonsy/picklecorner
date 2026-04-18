<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function markRead(string $id): void
    {
        $n = Auth::user()?->notifications()->where('id', $id)->first();
        $n?->markAsRead();
    }

    public function markAllRead(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();
        $this->close();
    }

    public function markReadAndGoRoute(string $notificationId, string $routeName): void
    {
        $this->markRead($notificationId);
        $this->redirect(route($routeName), navigate: true);
    }

    public function markReadAndGoUrl(string $notificationId, string $url): void
    {
        if ($url === '') {
            return;
        }
        $this->markRead($notificationId);
        $this->redirect($url, navigate: true);
    }

    public function render(): View
    {
        $user = Auth::user();
        $notifications = $user
            ? $user->notifications()->latest()->limit(25)->get()
            : collect();

        return view('livewire.notification-bell', [
            'notifications' => $notifications,
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}

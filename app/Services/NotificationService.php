<?php

namespace App\Services;

use App\Models\PortalNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a notification to a specific user.
     */
    public function notify(User|int $user, string $type, string $title, ?string $body = null, ?string $url = null): PortalNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        return PortalNotification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'url'     => $url,
        ]);
    }

    /**
     * Notify all internal users in the given department(s).
     * Pass department_id(s). If null, notifies all internal users.
     */
    public function notifyDepartment(int|array|null $departmentIds, string $type, string $title, ?string $body = null, ?string $url = null): void
    {
        $query = User::where('is_active', true)
            ->whereIn('role', ['admin', 'purchasing', 'graphic']);

        if ($departmentIds !== null) {
            $ids = (array) $departmentIds;
            $query->whereIn('department_id', $ids);
        }

        $query->each(function (User $user) use ($type, $title, $body, $url) {
            $this->notify($user, $type, $title, $body, $url);
        });
    }

    /**
     * Get unread count for a user.
     */
    public function unreadCount(User|int $user): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        return PortalNotification::where('user_id', $userId)->unread()->count();
    }

    /**
     * Get latest notifications for a user (read + unread).
     */
    public function latest(User|int $user, int $limit = 20): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return PortalNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark all unread notifications as read for a user.
     */
    public function markAllRead(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        PortalNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}

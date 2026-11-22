<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_notification');
    }

    public function view(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('view_notification')
            && $this->canAccessNotification($authUser, $notification);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_notification');
    }

    public function update(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('update_notification')
            && $this->canAccessNotification($authUser, $notification);
    }

    public function delete(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('delete_notification')
            && $this->canAccessNotification($authUser, $notification);
    }

    public function restore(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('restore_notification');
    }

    public function forceDelete(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('force_delete_notification');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_notification');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_notification');
    }

    public function replicate(AuthUser $authUser, Notification $notification): bool
    {
        return $authUser->can('replicate_notification');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_notification');
    }

    private function canAccessNotification(AuthUser $authUser, Notification $notification): bool
    {
        return $notification->user_id === $authUser->getAuthIdentifier()
            || ($authUser instanceof User && $authUser->hasRole('super_admin'));
    }
}

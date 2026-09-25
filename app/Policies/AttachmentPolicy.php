<?php

namespace App\Policies;

use App\Models\Attachment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AttachmentPolicy
{
    use HandlesAuthorization;

    public function view(AuthUser $authUser, Attachment $attachment): bool
    {
        return $this->canAccessParent($authUser, $attachment);
    }

    public function delete(AuthUser $authUser, Attachment $attachment): bool
    {
        return $attachment->user_id === $authUser->id
            || $this->canUpdateParent($authUser, $attachment);
    }

    private function canAccessParent(AuthUser $authUser, Attachment $attachment): bool
    {
        return $attachment->attachable && $authUser->can('view', $attachment->attachable);
    }

    private function canUpdateParent(AuthUser $authUser, Attachment $attachment): bool
    {
        return $attachment->attachable && $authUser->can('update', $attachment->attachable);
    }
}

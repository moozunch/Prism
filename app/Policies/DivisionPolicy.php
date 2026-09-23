<?php

namespace App\Policies;

use App\Models\Division;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DivisionPolicy
{
    use HandlesAuthorization;

    private function canManage(AuthUser $authUser): bool
    {
        return $authUser->hasRole(['admin', 'super_admin']);
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $this->canManage($authUser);
    }

    public function view(AuthUser $authUser, Division $division): bool
    {
        return $this->canManage($authUser);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->canManage($authUser);
    }

    public function update(AuthUser $authUser, Division $division): bool
    {
        return $this->canManage($authUser);
    }

    public function delete(AuthUser $authUser, Division $division): bool
    {
        return $this->canManage($authUser);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->canManage($authUser);
    }
}

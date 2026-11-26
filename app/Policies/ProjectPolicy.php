<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_project');
    }

    public function view(
        AuthUser $authUser,
        Project $project
    ): bool {
        if (!$authUser->can('view_project')) {
            return false;
        }

        return $this->canAccessProject(
            $authUser,
            $project
        );
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project');
    }

    public function update(
        AuthUser $authUser,
        Project $project
    ): bool {
        return $authUser->can('update_project')
            && $this->canAccessProject($authUser, $project);
    }

    public function delete(
        AuthUser $authUser,
        Project $project
    ): bool {
        return $authUser->can('delete_project')
            && $this->canAccessProject($authUser, $project);
    }

    public function restore(
        AuthUser $authUser,
        Project $project
    ): bool {
        return $authUser->can('restore_project');
    }

    public function forceDelete(
        AuthUser $authUser,
        Project $project
    ): bool {
        return $authUser->can('force_delete_project');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_project');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_project');
    }

    public function replicate(
        AuthUser $authUser,
        Project $project
    ): bool {
        return $authUser->can('replicate_project');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project');
    }

    private function canAccessProject(
        AuthUser $authUser,
        Project $project
    ): bool {
        if (!$authUser instanceof User) {
            return false;
        }

        if (
            $authUser->hasAnyRole([
                'admin',
                'super_admin',
            ])
        ) {
            return true;
        }

        return $project->members()
            ->where(
                'users.id',
                $authUser->getAuthIdentifier()
            )
            ->exists();
    }
}
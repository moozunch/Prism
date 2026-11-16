<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TicketPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_ticket');
    }

    public function view(
        AuthUser $authUser,
        Ticket $ticket
    ): bool {
        return $authUser->can('view_ticket')
            && $this->canAccessTicket($authUser, $ticket);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_ticket');
    }

    public function update(
        AuthUser $authUser,
        Ticket $ticket
    ): bool {
        return $authUser->can('update_ticket')
            && $this->canAccessTicket($authUser, $ticket);
    }

    public function delete(
        AuthUser $authUser,
        Ticket $ticket
    ): bool {
        return $authUser->can('delete_ticket')
            && $this->canAccessTicket($authUser, $ticket);
    }

    public function restore(
        AuthUser $authUser,
        Ticket $ticket
    ): bool {
        return $authUser->can('restore_ticket');
    }

    public function forceDelete(
        AuthUser $authUser,
        Ticket $ticket
    ): bool {
        return $authUser->can('force_delete_ticket');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_ticket');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_ticket');
    }

    public function replicate(
        AuthUser $authUser,
        Ticket $ticket
    ): bool {
        return $authUser->can('replicate_ticket');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_ticket');
    }

    private function canAccessTicket(
        AuthUser $authUser,
        Ticket $ticket
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

        $project = $ticket->project;

        if (!$project) {
            return false;
        }

        return $project->members()
            ->where(
                'users.id',
                $authUser->getAuthIdentifier()
            )
            ->exists();
    }
}
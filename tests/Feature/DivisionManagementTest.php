<?php

use App\Filament\Resources\Divisions\DivisionResource;
use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function divisionRoleUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    Role::findOrCreate($role, 'web');
    $user->assignRole($role);

    return $user;
}

it('creates and updates a division', function () {
    $division = Division::create([
        'name' => 'Operations',
        'description' => 'Operational teams',
    ]);

    expect($division->is_active)->toBeTrue()
        ->and($division->fresh()->name)->toBe('Operations');

    $division->update([
        'name' => 'Operations and Support',
        'is_active' => false,
    ]);

    expect($division->fresh()->only(['name', 'is_active']))->toBe([
        'name' => 'Operations and Support',
        'is_active' => false,
    ]);
});

it('allows admin and super admin users to manage divisions', function (string $role) {
    $user = divisionRoleUser($role);

    expect($user->can('viewAny', Division::class))->toBeTrue()
        ->and(DivisionResource::canViewAny())->toBeTrue();
})->with(['admin', 'super_admin']);

it('denies members from managing divisions', function () {
    $user = divisionRoleUser('member');

    expect($user->can('viewAny', Division::class))->toBeFalse()
        ->and(DivisionResource::canViewAny())->toBeFalse();
});

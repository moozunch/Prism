<?php

use App\Filament\Pages\OrganizationInformation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function organizationRoleUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    Role::findOrCreate($role, 'web');
    $user->assignRole($role);

    return $user;
}

it('creates and updates the deployment organization profile', function () {
    $organization = Organization::profile();

    expect($organization->name)->toBe(config('app.name'))
        ->and(Organization::query()->count())->toBe(1);

    $organization->update([
        'name' => 'Example Organization',
        'email' => 'contact@example.test',
        'phone' => '+1 555 0100',
        'address' => '1 Example Street',
        'description' => 'Deployment profile',
    ]);

    expect(Organization::profile()->fresh()->only([
        'name',
        'email',
        'phone',
        'address',
        'description',
    ]))->toBe([
        'name' => 'Example Organization',
        'email' => 'contact@example.test',
        'phone' => '+1 555 0100',
        'address' => '1 Example Street',
        'description' => 'Deployment profile',
    ]);
});

it('allows admin and super admin users to access organization information', function (string $role) {
    organizationRoleUser($role);

    expect(OrganizationInformation::canAccess())->toBeTrue();
})->with(['admin', 'super_admin']);

it('denies organization information access to members', function () {
    organizationRoleUser('member');

    expect(OrganizationInformation::canAccess())->toBeFalse();
});

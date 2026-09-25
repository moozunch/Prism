<?php

use App\Models\User;
use Database\Seeders\PrismRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds the PRISM business roles without removing legacy roles', function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'member', 'guard_name' => 'web']);

    app(PrismRoleSeeder::class)->run();

    expect(Role::whereIn('name', [
        'super_admin',
        'admin',
        'member',
        'division_head',
        'project_manager',
    ])->count())->toBe(5);
});

it('supports assigning PRISM roles and checking them on User', function () {
    app(PrismRoleSeeder::class)->run();
    $user = User::factory()->create();

    $user->assignRole(['division_head', 'project_manager']);

    expect($user->fresh()->isDivisionHead())->toBeTrue()
        ->and($user->fresh()->isProjectManager())->toBeTrue()
        ->and($user->fresh()->isOrganizationAdmin())->toBeFalse();
});

it('keeps organization admin distinct from the technical super admin role', function () {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    expect($admin->isOrganizationAdmin())->toBeTrue()
        ->and($superAdmin->isOrganizationAdmin())->toBeFalse();
});
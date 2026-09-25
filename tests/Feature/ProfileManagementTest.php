<?php

use App\Models\User;
use Filament\Auth\Pages\EditProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('allows a verified regular user to access the native profile page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Role::create([
        'name' => 'member',
        'guard_name' => 'web',
    ]);
    $user->assignRole('member');

    $this->actingAs($user)
        ->get('/admin/profile')
        ->assertOk();
});

it('uses the native profile page without role or division fields', function () {
    $profileSource = file_get_contents((new ReflectionClass(EditProfile::class))->getFileName());

    expect($profileSource)
        ->toContain("TextInput::make('name')")
        ->toContain("TextInput::make('email')")
        ->not->toContain("make('roles')")
        ->not->toContain("make('division_id')");
});
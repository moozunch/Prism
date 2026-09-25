<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('associates a user with a division', function () {
    $division = Division::create([
        'name' => 'Operations',
    ]);

    $user = User::factory()->create([
        'division_id' => $division->id,
    ]);

    expect($user->division->is($division))->toBeTrue()
        ->and($user->email)->not->toBeNull();
});

it('allows a user to have no division assigned', function () {
    $user = User::factory()->create(['division_id' => null]);

    expect($user->division)->toBeNull();
});

it('clears the user division assignment when the division is deleted', function () {
    $division = Division::create([
        'name' => 'Operations',
    ]);
    $user = User::factory()->create(['division_id' => $division->id]);

    $division->delete();

    expect($user->refresh()->division_id)->toBeNull();
});
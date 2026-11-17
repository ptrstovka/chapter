<?php

use App\Enums\Preference;
use App\Facades\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can update the studio user directory setting', function () {
    Settings::set(Preference::EnableStudioUserDirectory, false);

    $admin = User::factory()->admin()->create();

    $this
        ->actingAs($admin)
        ->patch(route('admin.settings.update'), [
            'enable_studio_user_directory' => true,
        ])
        ->assertRedirect();

    expect(Settings::boolean(Preference::EnableStudioUserDirectory))->toBeTrue();
});

test('studio creators can exact match one user by email when the directory is disabled', function () {
    Settings::set(Preference::EnableStudioUserDirectory, false);

    $creator = User::factory()->author()->create();
    $target = User::factory()->create([
        'email' => 'target@example.com',
        'name' => 'Target Learner',
    ]);

    $this
        ->actingAs($creator)
        ->getJson(route('studio.users.lookup', ['query' => 'TARGET@example.com']))
        ->assertOk()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.name', 'Target Learner')
        ->assertJsonPath('data.email', 'target@example.com')
        ->assertJsonPath('meta.lookupMode', 'exact_email')
        ->assertJsonPath('meta.searchable', false);
});

test('studio creators receive a not found contract for missing exact email lookups', function () {
    Settings::set(Preference::EnableStudioUserDirectory, false);

    $creator = User::factory()->author()->create();

    $this
        ->actingAs($creator)
        ->getJson(route('studio.users.lookup', ['query' => 'missing@example.com']))
        ->assertNotFound()
        ->assertJsonPath('message', 'No user was found for that email address.')
        ->assertJsonPath('data', null)
        ->assertJsonPath('meta.lookupMode', 'exact_email')
        ->assertJsonPath('meta.searchable', false);
});

test('studio creators can search users by name or email when the directory is enabled', function () {
    Settings::set(Preference::EnableStudioUserDirectory, true);

    $creator = User::factory()->author()->create();
    $target = User::factory()->create([
        'email' => 'ada@example.com',
        'name' => 'Ada Lovelace',
    ]);

    User::factory()->create([
        'email' => 'grace@example.com',
        'name' => 'Grace Hopper',
    ]);

    $this
        ->actingAs($creator)
        ->getJson(route('studio.users.lookup', ['query' => 'Ada']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $target->id)
        ->assertJsonPath('data.0.name', 'Ada Lovelace')
        ->assertJsonPath('data.0.email', 'ada@example.com')
        ->assertJsonMissingPath('data.0.is_admin')
        ->assertJsonPath('meta.lookupMode', 'search')
        ->assertJsonPath('meta.searchable', true);
});

test('admins can search users even when the studio directory is disabled', function () {
    Settings::set(Preference::EnableStudioUserDirectory, false);

    $admin = User::factory()->admin()->create();
    $target = User::factory()->create([
        'email' => 'searchable@example.com',
        'name' => 'Searchable Student',
    ]);

    $this
        ->actingAs($admin)
        ->getJson(route('studio.users.lookup', ['query' => 'Searchable']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $target->id)
        ->assertJsonPath('meta.lookupMode', 'search')
        ->assertJsonPath('meta.searchable', true)
        ->assertJsonPath('meta.directorySearchEnabled', false);
});

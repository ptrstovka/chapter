<?php

use App\Enums\AccessGroupVisibility;
use App\Models\AccessGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('allows admins to view the group list', function () {
    $admin = User::factory()->admin()->create();

    AccessGroup::factory()->create([
        'name' => 'Founding Learners',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.groups'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/AccessGroupListPage')
            ->has('groups.rows', 1)
        );
});

it('prevents non admins from managing groups', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.groups'))
        ->assertForbidden();
});

it('creates groups with public visibility by default', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->post(route('admin.groups.store'), [
            'name' => 'Beta Cohort',
            'description' => 'Early access learners',
        ]);

    $accessGroup = AccessGroup::query()->firstOrFail();

    $response->assertRedirect(route('admin.groups.edit', $accessGroup));

    expect($accessGroup)
        ->name->toBe('Beta Cohort')
        ->description->toBe('Early access learners')
        ->visibility->toBe(AccessGroupVisibility::Public);
});

it('requires unique group names', function () {
    $admin = User::factory()->admin()->create();

    AccessGroup::factory()->create([
        'name' => 'VIP Learners',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.groups.store'), [
            'name' => 'VIP Learners',
        ])
        ->assertSessionHasErrors('name');
});

it('updates group details', function () {
    $admin = User::factory()->admin()->create();
    $accessGroup = AccessGroup::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.groups.update', $accessGroup), [
            'name' => 'Partner Learners',
            'description' => null,
            'visibility' => AccessGroupVisibility::Private->value,
        ])
        ->assertRedirect();

    $accessGroup->refresh();

    expect($accessGroup)
        ->name->toBe('Partner Learners')
        ->description->toBeNull()
        ->visibility->toBe(AccessGroupVisibility::Private);
});

it('adds and removes group members in bulk', function () {
    $admin = User::factory()->admin()->create();
    $accessGroup = AccessGroup::factory()->create();
    $users = User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->post(route('admin.groups.users.store', $accessGroup), [
            'users' => $users->take(2)->pluck('id')->all(),
        ])
        ->assertRedirect();

    expect($accessGroup->users()->pluck('users.id')->all())
        ->toEqualCanonicalizing($users->take(2)->pluck('id')->all());

    $this->actingAs($admin)
        ->delete(route('admin.groups.users.destroy', $accessGroup), [
            'users' => [$users[0]->id],
        ])
        ->assertRedirect();

    expect($accessGroup->users()->pluck('users.id')->all())
        ->toEqualCanonicalizing([$users[1]->id]);
});

it('paginates group members without sending the whole user directory', function () {
    $admin = User::factory()->admin()->create();
    $group = AccessGroup::factory()->create();
    $members = User::factory()->count(32)
        ->sequence(fn (Sequence $sequence) => ['name' => sprintf('Member %02d', $sequence->index + 1)])
        ->create();
    $group->users()->attach($members);
    User::factory()->count(5)->create();

    $this->actingAs($admin)
        ->get(route('admin.groups.edit', ['accessGroup' => $group, 'members_page' => 2]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/AccessGroupFormPage')
            ->where('group.usersCount', 32)
            ->where('memberSearch', '')
            ->where('members.total', 32)
            ->where('members.currentPage', 2)
            ->where('members.lastPage', 3)
            ->where('members.perPage', 15)
            ->has('members.data', 15)
            ->where('members.data.0.id', $members[15]->id)
            ->where('members.data.14.id', $members[29]->id)
            ->missing('members.data.0.password')
            ->missing('members.data.0.createdAt')
            ->missing('availableUsers')
        );
});

it('searches only the current groups members by name and email while retaining the total member count', function () {
    $admin = User::factory()->admin()->create();
    $group = AccessGroup::factory()->create();
    $nameMatch = User::factory()->create(['name' => 'Target Learner', 'email' => 'name@example.test']);
    $emailMatch = User::factory()->create(['name' => 'Email Learner', 'email' => 'target@example.test']);
    $otherMember = User::factory()->create(['name' => 'Other Learner', 'email' => 'other@example.test']);
    $group->users()->attach([$nameMatch->id, $emailMatch->id, $otherMember->id]);
    $otherGroup = AccessGroup::factory()->create();
    $otherGroup->users()->attach(User::factory()->create(['name' => 'Target Outside']));

    $this->actingAs($admin)
        ->get(route('admin.groups.edit', ['accessGroup' => $group, 'members_search' => '  TARGET  ']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('group.usersCount', 3)
            ->where('memberSearch', 'TARGET')
            ->where('members.total', 2)
            ->has('members.data', 2)
            ->where('members.data.0.id', $emailMatch->id)
            ->where('members.data.1.id', $nameMatch->id)
        );
});

it('paginates available users and excludes members from every group member page', function () {
    $admin = User::factory()->admin()->create(['name' => 'Administrator', 'email' => 'admin@example.test']);
    $group = AccessGroup::factory()->create();
    $members = User::factory()->count(31)->create(['name' => 'Candidate Member']);
    $group->users()->attach($members);
    $available = User::factory()->count(25)
        ->sequence(fn (Sequence $sequence) => ['name' => sprintf('Candidate %02d', $sequence->index + 1)])
        ->create();
    AccessGroup::factory()->create()->users()->attach($available[10]);

    $this->actingAs($admin)
        ->getJson(route('admin.groups.users.available', [
            'accessGroup' => $group,
            'query' => '  CANDIDATE  ',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertExactJson([
            'data' => $available->slice(10, 10)->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->values()->all(),
            'currentPage' => 2,
            'lastPage' => 3,
            'perPage' => 10,
            'total' => 25,
        ]);
});

it('searches available users by name or email without leaking matching existing members', function () {
    $admin = User::factory()->admin()->create(['name' => 'Administrator', 'email' => 'admin@example.test']);
    $group = AccessGroup::factory()->create();
    $member = User::factory()->create(['name' => 'Target Member', 'email' => 'target-member@example.test']);
    $group->users()->attach($member);
    $nameMatch = User::factory()->create(['name' => 'Target New', 'email' => 'new@example.test']);
    $emailMatch = User::factory()->create(['name' => 'Email New', 'email' => 'target-new@example.test']);
    User::factory()->create(['name' => 'Other Person', 'email' => 'other@example.test']);

    $this->actingAs($admin)
        ->getJson(route('admin.groups.users.available', ['accessGroup' => $group, 'query' => 'tArGeT']))
        ->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $emailMatch->id)
        ->assertJsonPath('data.1.id', $nameMatch->id);
});

it('allows only admins to query available group members', function () {
    $group = AccessGroup::factory()->create();

    $this->getJson(route('admin.groups.users.available', $group))->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson(route('admin.groups.users.available', $group))
        ->assertForbidden();

    $this->actingAs(User::factory()->author()->create())
        ->getJson(route('admin.groups.users.available', $group))
        ->assertForbidden();
});

it('refreshes member totals after adding and removing users and clamps an emptied last page', function () {
    $admin = User::factory()->admin()->create();
    $group = AccessGroup::factory()->create();
    $members = User::factory()->count(16)
        ->sequence(fn (Sequence $sequence) => ['name' => sprintf('Cohort %02d', $sequence->index + 1)])
        ->create();
    $group->users()->attach($members);
    $group->users()->attach(User::factory()->create(['name' => 'Other Member', 'email' => 'other@example.test']));
    $editPage = route('admin.groups.edit', [
        'accessGroup' => $group,
        'members_search' => 'Cohort',
        'members_page' => 2,
    ]);

    $this->actingAs($admin)->from($editPage)
        ->delete(route('admin.groups.users.destroy', $group), ['users' => [$members[15]->id]])
        ->assertRedirect($editPage);

    $this->get($editPage)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('group.usersCount', 16)
            ->where('memberSearch', 'Cohort')
            ->where('members.total', 15)
            ->where('members.currentPage', 1)
            ->where('members.lastPage', 1)
            ->has('members.data', 15)
        );

    $this->getJson(route('admin.groups.users.available', ['accessGroup' => $group, 'query' => $members[15]->email]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.id', $members[15]->id);

    $this->from($editPage)
        ->post(route('admin.groups.users.store', $group), ['users' => [$members[15]->id]])
        ->assertRedirect($editPage);

    $this->get($editPage)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('group.usersCount', 17)
            ->where('members.total', 16)
            ->where('members.currentPage', 2)
            ->has('members.data', 1)
            ->where('members.data.0.id', $members[15]->id)
        );

    $this->getJson(route('admin.groups.users.available', ['accessGroup' => $group, 'query' => $members[15]->email]))
        ->assertOk()
        ->assertJsonPath('total', 0);
});

it('validates member search and pagination inputs', function (string $routeName, array $parameters, string $error) {
    $admin = User::factory()->admin()->create();
    $group = AccessGroup::factory()->create();

    $this->actingAs($admin)
        ->getJson(route($routeName, ['accessGroup' => $group, ...$parameters]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($error);
})->with([
    'long member search' => ['admin.groups.edit', ['members_search' => str_repeat('a', 192)], 'members_search'],
    'invalid member search' => ['admin.groups.edit', ['members_search' => ['name']], 'members_search'],
    'invalid member page' => ['admin.groups.edit', ['members_page' => 0], 'members_page'],
    'long available search' => ['admin.groups.users.available', ['query' => str_repeat('a', 192)], 'query'],
    'invalid available search' => ['admin.groups.users.available', ['query' => ['name']], 'query'],
    'invalid available page' => ['admin.groups.users.available', ['page' => 0], 'page'],
]);

it('rejects duplicate user ids when changing memberships', function (string $method, string $routeName) {
    $admin = User::factory()->admin()->create();
    $group = AccessGroup::factory()->create();
    $member = User::factory()->create();
    $group->users()->attach($member);

    $this->actingAs($admin)->{$method}(route($routeName, $group), ['users' => [$member->id, $member->id]])
        ->assertSessionHasErrors('users.0');

    expect($group->users()->count())->toBe(1);
})->with([
    ['post', 'admin.groups.users.store'],
    ['delete', 'admin.groups.users.destroy'],
]);

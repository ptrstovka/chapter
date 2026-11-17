<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccessGroupVisibility;
use App\Models\AccessGroup;
use App\Models\User;
use App\Table\Actions\DeleteAction;
use App\View\Layouts\AdminLayout;
use App\View\Models\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;
use StackTrace\Ui\SelectOption;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Actions;
use StackTrace\Ui\Table\Columns;

class AccessGroupController
{
    public function index()
    {
        Gate::authorize('viewAny', AccessGroup::class);

        $groups = Table::make(AccessGroup::query()->withCount('users'))
            ->searchable(function (Builder $builder, string $term) {
                $term = '%'.Str::lower($term).'%';

                $builder->where(function (Builder $builder) use ($term) {
                    $builder
                        ->where(DB::raw('lower(name)'), 'like', $term)
                        ->orWhere(DB::raw('lower(description)'), 'like', $term);
                });
            })
            ->withColumns([
                Columns\Text::make(__('Name'), 'name')
                    ->fontMedium()
                    ->link(fn (AccessGroup $accessGroup) => Link::to(route('admin.groups.edit', $accessGroup))),

                Columns\Badge::make(__('Visibility'), fn (AccessGroup $accessGroup) => $accessGroup->visibility->value)
                    ->label([
                        AccessGroupVisibility::Public->value => AccessGroupVisibility::Public->label(),
                        AccessGroupVisibility::Private->value => AccessGroupVisibility::Private->label(),
                    ])
                    ->variant([
                        AccessGroupVisibility::Public->value => 'positive',
                        AccessGroupVisibility::Private->value => 'default',
                    ])
                    ->width(32),

                Columns\Text::make(__('Members'), 'users_count')
                    ->numsTabular()
                    ->alignRight()
                    ->width(24),

                Columns\DateTime::make(__('Created At'), 'created_at')
                    ->sortable(using: 'created_at', default: Table\Direction::Desc),
            ])
            ->withActions([
                Actions\Link::make(__('Edit'), fn (AccessGroup $accessGroup) => route('admin.groups.edit', $accessGroup)),

                DeleteAction::make(AccessGroup::class)
                    ->can(fn (AccessGroup $accessGroup) => Gate::allows('delete', $accessGroup))
                    ->bulk(),
            ]);

        return Inertia::render('Admin/AccessGroupListPage', AdminLayout::make([
            'groups' => $groups,
        ])->breadcrumb(BreadcrumbItem::make(__('Groups'))));
    }

    public function create()
    {
        Gate::authorize('create', AccessGroup::class);

        return Inertia::render('Admin/AccessGroupFormPage', AdminLayout::make([
            'visibilityOptions' => $this->visibilityOptions(),
        ])->breadcrumb([
            BreadcrumbItem::make(__('Groups'), Link::to(route('admin.groups'))),
            BreadcrumbItem::make(__('New Group')),
        ]));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', AccessGroup::class);

        $validated = $this->validateGroup($request);

        $accessGroup = AccessGroup::create($validated);

        return to_route('admin.groups.edit', $accessGroup);
    }

    public function edit(Request $request, AccessGroup $accessGroup): Response
    {
        Gate::authorize('view', $accessGroup);

        $validated = $request->validate([
            'members_search' => ['nullable', 'string', 'max:191'],
            'members_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $accessGroup->loadCount('users');

        return Inertia::render('Admin/AccessGroupFormPage', AdminLayout::make([
            'group' => [
                'id' => $accessGroup->id,
                'name' => $accessGroup->name,
                'description' => $accessGroup->description,
                'visibility' => $accessGroup->visibility->value,
                'usersCount' => $accessGroup->users_count,
                'canDelete' => Gate::allows('delete', $accessGroup),
            ],
            'visibilityOptions' => $this->visibilityOptions(),
            ...$this->membershipProps(
                $accessGroup,
                trim($validated['members_search'] ?? ''),
                (int) ($validated['members_page'] ?? 1),
            ),
        ])->breadcrumb([
            BreadcrumbItem::make(__('Groups'), Link::to(route('admin.groups'))),
            BreadcrumbItem::make($accessGroup->name),
        ]));
    }

    public function update(Request $request, AccessGroup $accessGroup)
    {
        Gate::authorize('update', $accessGroup);

        $accessGroup->update($this->validateGroup($request, $accessGroup));

        return back();
    }

    public function destroy(AccessGroup $accessGroup)
    {
        Gate::authorize('delete', $accessGroup);

        $accessGroup->delete();

        return to_route('admin.groups');
    }

    public function attachUsers(Request $request, AccessGroup $accessGroup)
    {
        Gate::authorize('update', $accessGroup);

        $validated = $this->validateUsers($request);

        $accessGroup->users()->syncWithoutDetaching($validated['users']);

        return back();
    }

    public function detachUsers(Request $request, AccessGroup $accessGroup)
    {
        Gate::authorize('update', $accessGroup);

        $validated = $this->validateUsers($request);

        $accessGroup->users()->detach($validated['users']);

        return back();
    }

    public function availableUsers(Request $request, AccessGroup $accessGroup): JsonResponse
    {
        Gate::authorize('update', $accessGroup);

        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:191'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $users = $this->matchingUsers(trim($validated['query'] ?? ''))
            ->whereNotIn('users.id', $accessGroup->users()->select('users.id'))
            ->paginate(10, page: (int) ($validated['page'] ?? 1));

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user) => $this->userOption($user))->all(),
            'currentPage' => $users->currentPage(),
            'lastPage' => $users->lastPage(),
            'perPage' => $users->perPage(),
            'total' => $users->total(),
        ]);
    }

    /**
     * @return array{name: string, description: string|null, visibility: string}
     */
    protected function validateGroup(Request $request, ?AccessGroup $accessGroup = null): array
    {
        $uniqueName = Rule::unique(AccessGroup::class, 'name');

        if ($accessGroup) {
            $uniqueName->ignoreModel($accessGroup);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191', $uniqueName],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['nullable', Rule::enum(AccessGroupVisibility::class)],
        ]);

        return [
            'name' => $validated['name'],
            'description' => ($validated['description'] ?? null) ?: null,
            'visibility' => $validated['visibility'] ?? AccessGroupVisibility::Public->value,
        ];
    }

    /**
     * @return array{users: array<int, int>}
     */
    protected function validateUsers(Request $request): array
    {
        return $request->validate([
            'users' => ['required', 'array', 'min:1'],
            'users.*' => ['integer', 'distinct', Rule::exists(User::class, 'id')],
        ]);
    }

    /**
     * @return array<int, SelectOption>
     */
    protected function visibilityOptions(): array
    {
        return collect(AccessGroupVisibility::cases())
            ->map(fn (AccessGroupVisibility $visibility) => new SelectOption($visibility->label(), $visibility->value))
            ->values()
            ->all();
    }

    /**
     * @return array{members: Paginator, memberSearch: string}
     */
    protected function membershipProps(AccessGroup $accessGroup, string $search, int $page): array
    {
        $query = $this->matchingUsers($search)
            ->whereIn('users.id', $accessGroup->users()->select('users.id'));

        $members = $query->paginate(15, pageName: 'members_page', page: $page);

        if ($members->currentPage() > $members->lastPage()) {
            $members = $query->paginate(
                15,
                pageName: 'members_page',
                page: $members->lastPage(),
                total: $members->total(),
            );
        }

        return [
            'members' => Paginator::make($members
                ->withQueryString()
                ->through(fn (User $user) => $this->userOption($user))),
            'memberSearch' => $search,
        ];
    }

    protected function matchingUsers(string $search): Builder
    {
        return User::query()
            ->select('users.id', 'users.name', 'users.email')
            ->when($search !== '', function (Builder $query) use ($search) {
                $term = '%'.Str::lower($search).'%';

                $query->where(function (Builder $query) use ($term) {
                    $query->whereRaw('lower(users.name) like ?', [$term])
                        ->orWhereRaw('lower(users.email) like ?', [$term]);
                });
            })
            ->orderBy('users.name')
            ->orderBy('users.email')
            ->orderBy('users.id');
    }

    /**
     * @return array{id: int, name: string, email: string}
     */
    protected function userOption(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}

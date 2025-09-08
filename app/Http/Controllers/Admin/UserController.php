<?php

namespace App\Http\Controllers\Admin;

use App\Models\Author;
use App\Models\User;
use App\View\Layouts\AdminLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;
use StackTrace\Ui\SelectOption;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Actions;
use StackTrace\Ui\Table\Columns;

class UserController
{
    public function index()
    {
        $builder = User::query()->with('author');

        $users = Table::make($builder)
            ->searchable(function (Builder $builder, string $term) {
                $column = Str::contains($term, '@') ? 'email' : 'name';

                $builder->where(DB::raw("lower({$column})"), 'like', '%'.Str::lower($term).'%');
            })
            ->withColumns([
                Columns\Text::make(__('Name'), 'name')
                    ->fontMedium()
                    ->link(fn (User $user) => Link::to(route('admin.users.edit', $user))),

                Columns\Text::make(__('E-Mail'), 'email')
                    ->width(60),

                Columns\Text::make(__('Role'), fn (User $user) => $user->author ? __('Author') : __('Student'))
                    ->width(24),

                Columns\Icon::make(__('Admin'), fn (User $user) => $user->is_admin ? 'circle-check' : 'circle-x')
                    ->alignCenter()
                    ->style(function (Table\Style $style, User $user) {
                        if ($user->is_admin) {
                            $style->color('destructive');
                        } else {
                            $style->color('positive');
                        }
                    })
                    ->width(10),

                Columns\DateTime::make(__('Created At'), 'created_at')
                    ->sortable(using: 'created_at', default: Table\Direction::Desc),
            ])
            ->withActions([
                Actions\Link::make(__('Edit'), fn (User $user) => route('admin.users.edit', $user)),
            ]);

        return Inertia::render('Admin/UserListPage', AdminLayout::make([
            'users' => $users,
        ])->breadcrumb(BreadcrumbItem::make(__('Users'))));
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/UserFormPage', AdminLayout::make([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => $user->is_admin,
                'role' => $user->author ? 'author' : 'student',
                'author' => $user->author?->id,
            ],
            'authors' => Author::all()
                ->map(fn (Author $author) => new SelectOption($author->name, $author->id))
                ->sortBy('label')
                ->values(),
        ])->breadcrumb([
            BreadcrumbItem::make(__('Users'), Link::to(route('admin.users'))),
            BreadcrumbItem::make(__($user->name)),
        ]));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique(User::class, 'email')->ignoreModel($user)],
            'is_admin' => ['boolean'],
            'role' => ['required', 'string', Rule::in(['student', 'author'])],
        ]);

        $role = $request->input('role');
        $author = null;

        if ($role === 'author') {
            $request->validate([
                'author' => ['required', 'numeric', Rule::exists(Author::class, 'id')],
            ]);

            $author = Author::query()->findOrFail($request->input('author'));
        }

        $isAdmin = $request->boolean('is_admin');

        if (Auth::user()->is($user) && ! $isAdmin) {
            throw ValidationException::withMessages([
                'is_admin' => __('You cannot remove admin permissions from yourself.'),
            ]);
        }

        $user->fill([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'is_admin' => $isAdmin,
        ]);

        $user->author()->associate($author);

        $user->save();

        return back();
    }
}

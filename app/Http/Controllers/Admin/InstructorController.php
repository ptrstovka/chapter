<?php

namespace App\Http\Controllers\Admin;

use App\Models\Author;
use App\Models\TemporaryUpload;
use App\Rules\TemporaryUploadRule;
use App\View\Layouts\AdminLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Actions;
use StackTrace\Ui\Table\Columns;

class InstructorController
{
    public function index()
    {
        $builder = Author::query()->withCount('courses');

        $instructors = Table::make($builder)
            ->searchable(fn (Builder $builder, string $term) => $builder->where(DB::raw('lower(name)'), 'like', '%'.Str::lower($term).'%'))
            ->withColumns([
                Columns\Text::make(__('Name'), 'name')
                    ->link(fn (Author $author) => Link::to(route('admin.instructors.edit', $author)))
                    ->fontMedium(),

                Columns\Text::make(__('Courses'), 'courses_count')
                    ->numsTabular()
                    ->alignRight(),

                Columns\DateTime::make(__('Created At'), 'created_at')
                    ->sortable(using: 'created_at', default: Table\Direction::Desc),
            ])
            ->withActions([
                Actions\Link::make(__('Edit'), fn (Author $author) => route('admin.instructors.edit', $author)),
            ]);

        return Inertia::render('Admin/InstructorListPage', AdminLayout::make([
            'instructors' => $instructors,
        ])->breadcrumb(BreadcrumbItem::make(__('Instructors'))));
    }

    public function create()
    {
        return Inertia::render('Admin/InstructorFormPage', AdminLayout::make()->breadcrumb([
            BreadcrumbItem::make(__('Instructors'), Link::to(route('admin.instructors'))),
            BreadcrumbItem::make(__('New Instructor')),
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => TemporaryUploadRule::scope('InstructorAvatar'),
        ]);

        $instructor = new Author([
            'name' => $request->input('name'),
            'bio' => $request->input('bio'),
        ]);

        $avatarUploadToRemove = null;

        if ($avatarSource = $request->input('avatar')) {
            $avatarUpload = TemporaryUpload::findOrFailByUuid($avatarSource);
            $instructor->avatar_file_path = $avatarUpload->copyTo('public', 'author-avatars');
            $avatarUploadToRemove = $avatarUpload;
        }

        $instructor->save();

        $avatarUploadToRemove?->delete();

        return to_route('admin.instructors.edit', $instructor);
    }

    public function edit(Author $instructor)
    {
        return Inertia::render('Admin/InstructorFormPage', AdminLayout::make([
            'instructor' => [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'bio' => $instructor->bio,
                'avatarUrl' => $instructor->getAvatarUrl(),
                'canDelete' => $instructor->courses()->doesntExist(),
            ],
        ])->breadcrumb([
            BreadcrumbItem::make(__('Instructors'), Link::to(route('admin.instructors'))),
            BreadcrumbItem::make($instructor->name),
        ]));
    }

    public function update(Request $request, Author $instructor)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => TemporaryUploadRule::scope('InstructorAvatar'),
            'remove_avatar' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $instructor) {
            $instructor->fill([
                'name' => $request->input('name'),
                'bio' => $request->input('bio'),
            ]);

            $removeAvatar = $request->boolean('remove_avatar');
            $avatarSource = $request->input('avatar');
            $avatarToRemove = null;
            $avatarUploadToRemove = null;

            if ($removeAvatar && $instructor->avatar_file_path) {
                $avatarToRemove = $instructor->avatar_file_path;
                $instructor->avatar_file_path = null;
            } elseif ($avatarSource) {
                if ($instructor->avatar_file_path) {
                    $avatarToRemove = $instructor->avatar_file_path;
                }

                $avatarUpload = TemporaryUpload::findOrFailByUuid($avatarSource);
                $instructor->avatar_file_path = $avatarUpload->copyTo('public', 'author-avatars');
                $avatarUploadToRemove = $avatarUpload;
            }

            $instructor->save();

            if ($avatarToRemove) {
                Storage::disk('public')->delete($avatarToRemove);
            }

            $avatarUploadToRemove?->delete();
        });

        return back();
    }

    public function destroy(Author $instructor)
    {
        abort_if($instructor->courses()->exists(), 400);

        $instructor->delete();

        return to_route('admin.instructors');
    }
}

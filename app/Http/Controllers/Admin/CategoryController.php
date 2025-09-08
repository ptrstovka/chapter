<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Support\Slug;
use App\View\Layouts\AdminLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use StackTrace\Ui\Breadcrumbs\BreadcrumbItem;
use StackTrace\Ui\Link;
use StackTrace\Ui\Table;
use StackTrace\Ui\Table\Columns;
use StackTrace\Ui\Table\Actions;

class CategoryController
{
    public function index()
    {
        $builder = Category::query()
            ->withCount('courses');

        $categories = Table::make($builder)
            ->searchable(fn (Builder $builder, string $term) => $builder->where(DB::raw('lower(title)'), 'like', '%'.Str::lower($term).'%'))
            ->withColumns([
                Columns\Text::make(__('Title'), 'title')
                    ->fontMedium()
                    ->link(fn (Category $category) => Link::to(route('admin.categories.edit', $category))),

                Columns\Text::make(__('Courses'), 'courses_count')
                    ->numsTabular()
                    ->alignEnd()
                    ->width(24),
            ])
            ->withActions([
                Actions\Link::make(__('Edit'), fn (Category $category) => route('admin.categories.edit', $category)),
            ]);

        return Inertia::render('Admin/CategoryListPage', AdminLayout::make([
            'categories' => $categories,
        ])->breadcrumb(BreadcrumbItem::make(__('Categories'))));
    }

    public function create()
    {
        return Inertia::render('Admin/CategoryFormPage', AdminLayout::make()->breadcrumb([
            BreadcrumbItem::make(__('Categories'), Link::to(route('admin.categories'))),
            BreadcrumbItem::make(__('New Category')),
        ]));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:191'],
        ]);

        $title = $request->input('title');
        $slug = Slug::unique($title, Category::class, 'slug');

        $category = Category::create([
            'title' => $title,
            'slug' => $slug,
        ]);

        return to_route('admin.categories.edit', $category);
    }

    public function edit(Category $category)
    {
        return Inertia::render('Admin/CategoryFormPage', AdminLayout::make([
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'slug' => $category->slug,
                'canDelete' => $category->courses()->doesntExist(),
            ],
        ])->breadcrumb([
            BreadcrumbItem::make(__('Categories'), Link::to(route('admin.categories'))),
            BreadcrumbItem::make($category->title),
        ]));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:191'],
        ]);

        $title = $request->input('title');

        if ($category->title != $title) {
            $category->update([
                'title' => $title,
                'slug' => Slug::unique($title, Category::class, 'slug'),
            ]);
        }

        return back();
    }

    public function destroy(Category $category)
    {
        abort_if($category->courses()->exists(), 400);

        $category->delete();

        return to_route('admin.categories');
    }
}

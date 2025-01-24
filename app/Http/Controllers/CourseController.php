<?php


namespace App\Http\Controllers;


use App\Models\Category;
use App\Models\Course;
use App\View\Models\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController
{
    public function index(Request $request)
    {
        $categories = Category::all()->map(fn (Category $category) => [
            'value' => $category->slug,
            'title' => $category->title,
        ])->sortBy('title')->values();

        $category = null;

        if ($request->has('category')) {
            $category = Category::query()->firstWhere('slug', $request->input('category'));
        }

        $courses = Course::query()
            ->with(['author'])
            ->when($category, function (Builder $builder, Category $category) {
                $builder->whereBelongsTo($category);
            })
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Course $course) => [
                'title' => $course->title,
                'url' => route('courses.show', $course->slug),
                'coverImageUrl' => $course->getCoverImageUrl(),
                'duration' => $course->getDurationLabel(),
                'author' => [
                    'name' => $course->author->name,
                ],
            ]);

        return Inertia::render('Courses/CourseList', [
            'categories' => $categories,
            'category' => $category?->title,
            'courses' => Paginator::make($courses),
        ]);
    }

    public function show(Course $course)
    {
        //
    }
}

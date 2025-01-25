<?php


namespace App\Http\Controllers;


use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\View\Models\Paginator;
use App\View\Models\VideoSource;
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
        $course->load('chapters.lessons.video');

        return Inertia::render('Courses/CourseDetail', [
            'id' => $course->uuid,
            'title' => $course->title,
            'trailer' => VideoSource::for($course->trailer),
            'description' => $course->description,
            'author' => [
                'name' => $course->author->name,
                'avatarUrl' => $course->author->getAvatarUrl(),
                'bio' => $course->author->bio,
            ],
            'chapters' => $course->chapters->sortBy('position')->values()->map(fn (Chapter $chapter) => [
                'id' => $chapter->uuid,
                'title' => $chapter->title,
                'lessons' => $chapter->lessons->sortBy('position')->values()->map(fn (Lesson $lesson) => [
                    'id' => $lesson->uuid,
                    'title' => $lesson->title,
                    'duration' => $lesson->video?->getDurationLabel(),
                ]),
            ]),
        ]);
    }
}

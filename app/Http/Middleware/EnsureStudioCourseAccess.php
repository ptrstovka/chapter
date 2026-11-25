<?php

namespace App\Http\Middleware;

use App\Enums\CourseEditorContext;
use App\Models\Course;
use App\Models\CourseDraft;
use App\Support\CourseDrafts;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudioCourseAccess
{
    public function __construct(private CourseDrafts $drafts) {}

    public function handle(Request $request, Closure $next): Response
    {
        $course = $request->route('course');
        if (! $course instanceof Course) {
            return $next($request);
        }

        Gate::authorize('view', $course);
        $context = CourseEditorContext::forRequest($request);
        $mutatesContent = $request->routeIs(...array_map($context->routeName(...), [
            'courses.update',
            'course.chapters.store', 'course.chapters.update', 'course.chapters.destroy', 'course.chapters.sort',
            'course.lessons.store', 'course.lessons.update', 'course.lessons.destroy', 'course.lessons.move',
        ]));
        $deletesCourse = $request->routeIs($context->routeName('courses.destroy'));

        if (! $mutatesContent && ! $deletesCourse) {
            $this->bindContent($request, $course->editorContent());

            return $next($request);
        }

        return DB::transaction(function () use ($course, $request, $next, $deletesCourse) {
            $current = Course::query()->lockForUpdate()->findOrFail($course->id);
            Gate::authorize($deletesCourse ? 'delete' : 'update', $current);
            abort_unless($deletesCourse ? $current->canBeDeleted() : $current->canBeUpdated(), 409, __('This course cannot be edited.'));

            if ($current->hasPublication()) {
                $this->drafts->verifyRevision($request, $current);
            }

            $request->route()->setParameter('course', $current);
            if ($deletesCourse) {
                $this->drafts->remove($current);

                return $next($request);
            }

            $content = $this->drafts->edit($current);
            $this->bindContent($request, $content);
            $response = $next($request);

            if ($response->exception ?? null) {
                throw $response->exception;
            }

            if ($content instanceof CourseDraft) {
                $this->drafts->changed($current, $content);
            }

            return $response;
        });
    }

    private function bindContent(Request $request, Course|CourseDraft $content): void
    {
        $chapter = $request->route('chapter');
        if ($chapter !== null) {
            $chapter = $content->chapters()->where('uuid', $chapter)->firstOrFail();
            $request->route()->setParameter('chapter', $chapter);
        }

        if (($lesson = $request->route('lesson')) !== null) {
            $lesson = $content->lessons()->where('uuid', $lesson)
                ->when($chapter !== null, fn ($query) => $query->where('chapter_id', $chapter->id))
                ->firstOrFail();
            $request->route()->setParameter('lesson', $lesson);
        }
    }
}

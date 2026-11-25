<?php

namespace App\Http\Middleware;

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
        $mutatesContent = $request->routeIs(
            'studio.courses.update',
            'studio.course.chapters.store', 'studio.course.chapters.update', 'studio.course.chapters.destroy', 'studio.course.chapters.sort',
            'studio.course.lessons.store', 'studio.course.lessons.update', 'studio.course.lessons.destroy', 'studio.course.lessons.move',
        );
        $deletesCourse = $request->routeIs('studio.courses.destroy');

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

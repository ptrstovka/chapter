<?php

namespace App\Http\Controllers\Studio;

use App\Enums\AccessGroupVisibility;
use App\Enums\CourseAccessMode;
use App\Enums\CourseAccessRequestStatus;
use App\Enums\Preference;
use App\Facades\Settings;
use App\Http\Requests\Studio\UpdateCourseAccessSettingsRequest;
use App\Models\AccessGroup;
use App\Models\Course;
use App\Models\CourseGroupAccess;
use App\Models\CourseUserAccess;
use App\Models\User;
use App\View\Layouts\CourseLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use StackTrace\Ui\SelectOption;

class CourseAccessController
{
    public function show(Course $course)
    {
        Gate::authorize('update', $course);

        $user = auth()->user();

        return Inertia::render('Studio/CourseAccess', CourseLayout::make($course, [
            'accessMode' => $course->access_mode->value,
            'accessModeOptions' => $this->accessModeOptions(),
            'directGrants' => $this->directGrants($course),
            'groupGrants' => $this->groupGrants($course, $user->is_admin),
            'availableGroups' => $this->availableGroups($course, $user->is_admin),
            'pendingRequests' => $this->pendingRequests($course),
            'userDirectory' => [
                'searchable' => (bool) ($user->is_admin || Settings::boolean(Preference::EnableStudioUserDirectory)),
                'directorySearchEnabled' => Settings::boolean(Preference::EnableStudioUserDirectory),
            ],
        ]));
    }

    public function update(UpdateCourseAccessSettingsRequest $request, Course $course)
    {
        $course->update([
            'access_mode' => $request->accessMode(),
        ]);

        return back();
    }

    /**
     * @return array<int, array{label: string, value: string, description: string}>
     */
    protected function accessModeOptions(): array
    {
        return [
            new SelectOption(__('Public'), CourseAccessMode::Public->value, [
                'description' => __('Anyone signed in can enroll.'),
            ]),
            new SelectOption(__('Approval required'), CourseAccessMode::RequestOnly->value, [
                'description' => __('Learners can find the course and request access before enrolling.'),
            ]),
            new SelectOption(__('Private'), CourseAccessMode::Private->value, [
                'description' => __('Only directly granted users or members of granted groups can access it.'),
            ]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function directGrants(Course $course): array
    {
        return $course
            ->userAccessGrants()
            ->with(['user:id,name,email', 'grantedBy:id,name,email'])
            ->get()
            ->sortBy(fn (CourseUserAccess $grant) => Str::lower($grant->user->name.' '.$grant->user->email))
            ->map(fn (CourseUserAccess $grant) => [
                'id' => $grant->id,
                'user' => $this->userOption($grant->user),
                'grantedBy' => $this->userOption($grant->grantedBy),
                'grantedAt' => $grant->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function groupGrants(Course $course, bool $includePrivate): array
    {
        return $course
            ->groupAccessGrants()
            ->with([
                'accessGroup' => fn ($query) => $query->withCount('users'),
                'grantedBy:id,name,email',
            ])
            ->when(! $includePrivate, fn (Builder $query) => $query->whereHas(
                'accessGroup',
                fn (Builder $query) => $query->where('visibility', AccessGroupVisibility::Public->value),
            ))
            ->get()
            ->sortBy(fn (CourseGroupAccess $grant) => Str::lower($grant->accessGroup->name))
            ->map(fn (CourseGroupAccess $grant) => [
                'id' => $grant->id,
                'group' => $this->groupOption($grant->accessGroup),
                'grantedBy' => $this->userOption($grant->grantedBy),
                'grantedAt' => $grant->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function availableGroups(Course $course, bool $includePrivate): array
    {
        $grantedGroupIds = $course->groupAccessGrants()->pluck('access_group_id');

        return AccessGroup::query()
            ->withCount('users')
            ->whereNotIn('id', $grantedGroupIds)
            ->when(! $includePrivate, fn (Builder $query) => $query->where('visibility', AccessGroupVisibility::Public->value))
            ->orderBy('name')
            ->get()
            ->map(fn (AccessGroup $accessGroup) => $this->groupOption($accessGroup))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function pendingRequests(Course $course): array
    {
        return $course
            ->accessRequests()
            ->with('user:id,name,email')
            ->where('status', CourseAccessRequestStatus::Pending)
            ->oldest()
            ->get()
            ->map(fn ($request) => [
                'id' => $request->id,
                'user' => $this->userOption($request->user),
                'requestedAt' => $request->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    protected function userOption(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * @return array{id: int, name: string, description: string|null, visibility: string, visibilityLabel: string, usersCount: int}
     */
    protected function groupOption(AccessGroup $accessGroup): array
    {
        return [
            'id' => $accessGroup->id,
            'name' => $accessGroup->name,
            'description' => $accessGroup->description,
            'visibility' => $accessGroup->visibility->value,
            'visibilityLabel' => $accessGroup->visibility->label(),
            'usersCount' => $accessGroup->users_count ?? 0,
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\CourseAccessMode;
use App\Enums\CourseStatus;
use App\Enums\TextContentType;
use App\Jobs\CalculateCourseDuration;
use App\Jobs\PublishCourse;
use App\Jobs\SortLessonsWithinCourse;
use App\Models\Concerns\HasUuid;
use App\Support\Duration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

/**
 * @property string $title
 * @property CourseStatus $status
 * @property CourseAccessMode $access_mode
 * @property string|null $description
 * @property int|null $duration_seconds
 * @property string|null $cover_image_file_path
 * @property Author $author
 * @property Video|null $trailer
 * @property Category|null $category
 * @property TextContentType $description_type
 * @property string|null $slug
 * @property Collection<int, Chapter> $chapters
 * @property Collection<int, \App\Models\Resource> $resources
 * @property Collection<int, Lesson> $lessons
 * @property Collection<int, CourseEnrollment> $enrollments
 * @property Collection<int, CourseUserAccess> $userAccessGrants
 * @property Collection<int, CourseGroupAccess> $groupAccessGrants
 * @property Collection<int, CourseAccessRequest> $accessRequests
 *
 * @method static \Database\Factories\CourseFactory factory($count = null, $state = [])
 */
class Course extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    // TODO: Add prunable to delete soft deleted courses.

    protected $guarded = false;

    protected $attributes = [
        'access_mode' => 'public',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'access_mode' => CourseAccessMode::class,
            'description_type' => TextContentType::class,
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function userAccessGrants(): HasMany
    {
        return $this->hasMany(CourseUserAccess::class);
    }

    public function groupAccessGrants(): HasMany
    {
        return $this->hasMany(CourseGroupAccess::class);
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(CourseAccessRequest::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_courses');
    }

    public function scopeSearch(Builder $builder, string $term): void
    {
        // TODO: Add full text search

        $builder->where(DB::raw('lower(title)'), 'like', '%'.Str::lower($term).'%');
    }

    public function scopeVisibleToLearner(Builder $builder, User $user): void
    {
        $builder->where(function (Builder $builder) use ($user) {
            $builder
                ->whereIn('access_mode', [
                    CourseAccessMode::Public->value,
                    CourseAccessMode::RequestOnly->value,
                ])
                ->orWhere(function (Builder $builder) use ($user) {
                    $builder->withAccessGrantFor($user);
                });
        });
    }

    public function scopeAccessibleToLearner(Builder $builder, User $user): void
    {
        $builder->where(function (Builder $builder) use ($user) {
            $builder
                ->where('access_mode', CourseAccessMode::Public->value)
                ->orWhere(function (Builder $builder) use ($user) {
                    $builder->withAccessGrantFor($user);
                });
        });
    }

    public function scopeWithAccessGrantFor(Builder $builder, User $user): void
    {
        $builder->where(function (Builder $builder) use ($user) {
            $builder
                ->whereHas('userAccessGrants', fn (Builder $builder) => $builder->where('user_id', $user->id))
                ->orWhereHas('groupAccessGrants.accessGroup.users', fn (Builder $builder) => $builder->where('users.id', $user->id));
        });
    }

    public function isVisibleToLearner(User $user): bool
    {
        if ($this->access_mode === CourseAccessMode::Public || $this->access_mode === CourseAccessMode::RequestOnly) {
            return true;
        }

        return $this->hasAccessGrantFor($user);
    }

    public function isAccessibleToLearner(User $user): bool
    {
        if ($this->access_mode === CourseAccessMode::Public) {
            return true;
        }

        return $this->hasAccessGrantFor($user);
    }

    public function hasAccessGrantFor(User $user): bool
    {
        if ($this->userAccessGrants()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $this
            ->groupAccessGrants()
            ->whereHas('accessGroup.users', fn (Builder $builder) => $builder->where('users.id', $user->id))
            ->exists();
    }

    /**
     * Publish the course.
     */
    public function publish(): void
    {
        if ($this->status === CourseStatus::Published) {
            return;
        }

        if ($this->status === CourseStatus::Publishing) {
            throw new LogicException('The course is already being published.');
        }

        $jobs = $this->lessons()
            ->with(['video'])
            ->get()
            ->map(fn (Lesson $lesson) => $lesson->video)
            ->push($this->trailer)
            ->filter()
            ->values()
            ->map(fn (Video $video) => $video->getProcessingJobs())
            ->flatten(1);

        $chain = [];

        if ($jobs->isNotEmpty()) {
            $chain[] = Bus::batch($jobs);
        }

        $chain[] = new SortLessonsWithinCourse($this->withoutRelations());
        $chain[] = new CalculateCourseDuration($this->withoutRelations());
        $chain[] = new PublishCourse($this->withoutRelations());

        $course = $this->withoutRelations();

        $this->update([
            'status' => CourseStatus::Publishing,
            'failure_reason' => null,
        ]);

        Bus::chain($chain)->catch(function (Throwable $exception) use ($course) {
            $course->update([
                'status' => CourseStatus::PublishFailure,
                'failure_reason' => $exception->getMessage(),
            ]);
        })->dispatch();
    }

    /**
     * Unpublish the course.
     */
    public function unpublish(): void
    {
        $this->update([
            'status' => CourseStatus::Unpublished,
        ]);
    }

    /**
     * Retrieve total course duration as label.
     */
    public function getDurationLabel(): ?string
    {
        return $this->duration_seconds
            ? Duration::seconds($this->duration_seconds)->format()
            : null;
    }

    /**
     * Retrieve URl to the cover image.
     */
    public function getCoverImageUrl(): ?string
    {
        if ($this->cover_image_file_path) {
            return Storage::disk(App::contentDisk())->url($this->cover_image_file_path);
        }

        return null;
    }

    /**
     * Determine whether a course can be published.
     */
    public function canBePublished(): bool
    {
        if ($this->status === CourseStatus::Draft || $this->status === CourseStatus::Unpublished) {
            if (! $this->title) {
                return false;
            }

            if (! $this->category_id) {
                return false;
            }

            if ($this->hasAttribute('lessons_count')) {
                if ($this->lessons_count <= 0) {
                    return false;
                }
            } else {
                if ($this->relationLoaded('lessons')) {
                    if ($this->lessons->isEmpty()) {
                        return false;
                    }
                } else {
                    if ($this->lessons()->doesntExist()) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether a course can be unpublished.
     */
    public function canBeUnpublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    /**
     * Determine whether the course can be updated by the user.
     */
    public function canBeUpdated(): bool
    {
        return $this->status === CourseStatus::Draft || $this->status === CourseStatus::Unpublished;
    }

    /**
     * Determine whether the course can be deleted by the user.
     */
    public function canBeDeleted(): bool
    {
        return $this->status === CourseStatus::Draft || $this->status === CourseStatus::Unpublished;
    }

    /**
     * Sort lessons within a course.
     */
    public function sortLessonsWithinCourse(): void
    {
        $this
            ->lessons()
            ->select('lessons.*')
            ->join('chapters', 'chapters.id', 'lessons.chapter_id')
            ->orderBy('chapters.position')
            ->orderBy('lessons.position')
            ->get()
            ->each(function (Lesson $lesson, int $idx) {
                $lesson->position_within_course = $idx + 1;
                $lesson->save();
            });
    }
}

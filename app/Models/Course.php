<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Jobs\CalculateCourseDuration;
use App\Jobs\PublishCourse;
use App\Models\Concerns\HasUuid;
use App\Support\Duration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Throwable;

/**
 * @property string $title
 * @property \App\Enums\CourseStatus $status
 * @property string|null $description
 * @property int|null $duration_seconds
 * @property string|null $cover_image_file_path
 * @property \App\Models\Author $author
 * @property \App\Models\Video|null $trailer
 * @property \App\Models\Category $category
 * @property string $slug
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Chapter> $chapters
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Resource> $resources
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lesson> $lessons
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\CourseEnrollment> $enrollments
 *
 * @method static \Database\Factories\CourseFactory factory($count = null, $state = [])
 */
class Course extends Model
{
    use HasFactory, HasUuid;

    protected $guarded = false;

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
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

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorite_courses');
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

        if ($this->status === CourseStatus::Unpublished) {
            $this->update([
                'status' => CourseStatus::Published,
            ]);

            return;
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
            return Storage::disk(config('filesystems.content_disk'))->url($this->cover_image_file_path);
        }

        return null;
    }
}

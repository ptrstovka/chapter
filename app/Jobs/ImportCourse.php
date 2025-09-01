<?php

namespace App\Jobs;

use App\Enums\CourseStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImportCourse implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(
        public string $folder,
        public bool $publish,
    ) {}

    public function handle(): void
    {
        if (! File::exists($this->folder)) {
            $this->fail("The folder [$this->folder] does not exist.");

            return;
        }

        if (! File::isDirectory($this->folder)) {
            $this->fail("The [$this->folder] is not a directory.");

            return;
        }

        $sourceStorage = Storage::build([
            'driver' => 'local',
            'root' => realpath($this->folder),
        ]);

        $destinationStorage = Storage::disk(config('filesystems.content_disk'));

        if (! $sourceStorage->exists('manifest.json')) {
            $this->fail('The manifest file does not exist.');

            return;
        }

        $manifest = json_decode($sourceStorage->get('manifest.json'), true);

        $title = Arr::get($manifest, 'title');

        if (! $title) {
            $this->fail('The course does not have a title.');

            return;
        }

        $chapters = collect(Arr::get($manifest, 'chapters', []));
        if ($chapters->isEmpty()) {
            $this->fail('No chapters in the course.');

            return;
        }

        $categoryTitle = collect(Arr::get($manifest, 'stats', []))->firstWhere('name', 'Kategória');
        $categoryTitle = is_array($categoryTitle) ? Arr::get($categoryTitle, 'value') : null;

        if (! $categoryTitle) {
            $this->fail('No category specified.');

            return;
        }

        $category = Category::query()->firstWhere('title', $categoryTitle) ?: Category::create([
            'title' => $categoryTitle,
            'slug' => Str::slug($categoryTitle),
        ]);

        $author = null;

        $storeImageAsset = function (string $name, string $folder) use ($sourceStorage, $destinationStorage) {
            $path = "assets/{$name}";

            if (! $sourceStorage->exists($path)) {
                return null;
            }

            if (! $destinationStorage->exists($folder)) {
                $destinationStorage->makeDirectory($folder);
            }

            $ext = match ($sourceStorage->mimeType($path)) {
                'image/jpeg' => 'jpeg',
                'image/png' => 'png',
                'image/svg+xml' => 'svg',
            };

            $fileName = Str::random(32).'.'.$ext;

            $destinationStorage->writeStream("{$folder}/{$fileName}", $sourceStorage->readStream($path));

            return "{$folder}/{$fileName}";
        };

        if ($authorSource = Arr::get($manifest, 'author')) {
            $name = Arr::get($authorSource, 'name');
            if (! $name) {
                $this->fail('Author name could not be resolved');

                return;
            }

            $author = Author::query()->firstWhere('name', $name);

            if (! $author) {
                $author = Author::create([
                    'name' => $name,
                    'avatar_file_path' => $storeImageAsset('author_avatar', 'author-avatars'),
                    'bio' => Arr::get($authorSource, 'bio'),
                ]);
            }
        } else {
            $this->fail('The course does not have author.');

            return;
        }

        $resolveVideoPath = function (string $id) use ($sourceStorage) {
            if (in_array($id, ['872538016', '229241381', '928468405'])) {
                return null;
            }

            $path = "videos/$id";

            if ($sourceStorage->exists("{$path}.mp4")) {
                return $path.'.mp4';
            } elseif ($sourceStorage->exists("{$path}.webm")) {
                throw new InvalidArgumentException('This video is webm type. Might be an issue tho');
            }

            throw new InvalidArgumentException("Unable to find video file for id {$id}");
        };

        $trailerPath = null;
        if ($trailerVideoId = Arr::get($manifest, 'preview_video_id')) {
            $trailerPath = $resolveVideoPath($trailerVideoId);
        }

        $uploadVideo = function (string $path) use ($sourceStorage, $destinationStorage) {
            $ext = File::extension($path);
            $name = Str::random(32).".{$ext}";

            if (! $destinationStorage->exists('course-videos')) {
                $destinationStorage->makeDirectory('course-videos');
            }

            $destinationStorage->writeStream("course-videos/{$name}", $sourceStorage->readStream($path));

            return Video::create([
                'file_path' => "course-videos/{$name}",
            ]);
        };

        $trailerVideo = $trailerPath ? $uploadVideo($trailerPath) : null;

        $slug = basename($this->folder);

        /** @var Course $course */
        $course = Course::create([
            'status' => CourseStatus::Draft,
            'author_id' => $author->id,
            'title' => $title,
            'slug' => $slug, // Str::slug($title),
            'description' => Arr::get($manifest, 'description'),
            'category_id' => $category->id,
            'trailer_id' => $trailerVideo?->id,
            'cover_image_file_path' => $storeImageAsset('cover_full', 'course-covers') ?: $storeImageAsset('cover', 'course-covers'),
        ]);

        $resources = collect(Arr::get($manifest, 'attachments', []))->mapWithKeys(function (array $attachment) use ($course, $sourceStorage, $destinationStorage) {
            if (! $destinationStorage->exists('course-resources')) {
                $destinationStorage->makeDirectory('course-resources');
            }

            $path = "attachments/{$attachment['id']}";

            if (! $sourceStorage->exists($path)) {
                throw new InvalidArgumentException("Attachment {$attachment['id']} does not exist.");
            }

            $mime = $sourceStorage->mimeType($path);
            $size = $sourceStorage->size($path);

            $name = Str::random(32);

            $destinationStorage->writeStream("course-resources/{$name}", $sourceStorage->readStream($path));

            $resource = $course->resources()->create([
                'file_path' => "course-resources/{$name}",
                'client_file_name' => $attachment['file_name'],
                'mime_type' => $mime,
                'size' => $size,
            ]);

            return [$attachment['id'] => $resource->id];
        });

        $lessonPosition = 1;

        $chapters->sortBy('position')->values()->each(function (array $chapterSource, int $idx) use ($course, $resolveVideoPath, $uploadVideo, $resources, &$lessonPosition) {
            $title = Arr::get($chapterSource, 'title');

            $chapter = Chapter::create([
                'title' => $title,
                'position' => $idx + 1,
                'course_id' => $course->id,
            ]);

            collect(Arr::get($chapterSource, 'episodes', []))->sortBy('position')->values()->each(function (array $episodeSource) use ($chapter, $resolveVideoPath, $uploadVideo, $resources, $course, &$lessonPosition) {
                $video = null;
                if ($videoId = Arr::get($episodeSource, 'video_id')) {
                    if ($path = $resolveVideoPath($videoId)) {
                        $video = $uploadVideo($path);
                    }
                }

                $title = Arr::get($episodeSource, 'title');

                /** @var Lesson $lesson */
                $lesson = Lesson::create([
                    'slug' => Str::slug($title),
                    'title' => $title,
                    'description' => Arr::get($episodeSource, 'description'),
                    'position' => $lessonPosition,
                    'chapter_id' => $chapter->id,
                    'video_id' => $video?->id,
                    'course_id' => $course->id,
                ]);

                $lessonPosition++;

                $attachments = collect(Arr::get($episodeSource, 'attachments', []));
                if ($attachments->isNotEmpty()) {
                    $ids = $attachments->pluck('id')->map(fn (string $id) => $resources->get($id));

                    $lesson->resources()->attach($ids);
                }
            });
        });

        if ($this->publish) {
            $course->publish();
        }
    }
}

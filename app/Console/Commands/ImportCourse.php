<?php

namespace App\Console\Commands;

use App\Enums\CourseStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportCourse extends Command
{
    protected $signature = 'course:import {dir}';

    protected $description = 'Import course from folder';

    public function handle(): int
    {
        $dir = $this->argument('dir');

        if (! File::exists($dir)) {
            $this->fail("The folder [$dir] does not exist.");
        }

        if (! File::isDirectory($dir)) {
            $this->fail("The [$dir] is not a directory.");
        }

        $manifestPath = "{$dir}/manifest.json";

        if (! File::exists($manifestPath)) {
            $this->fail('The manifest file does not exist.');
        }

        $manifest = File::json($manifestPath);

        $title = Arr::get($manifest, 'title');

        if (! $title) {
            $this->fail('The course does not have a title.');
        }

        $chapters = collect(Arr::get($manifest, 'chapters', []));
        if ($chapters->isEmpty()) {
            $this->fail('No chapters in the course.');
        }

        $categoryTitle = collect(Arr::get($manifest, 'stats', []))->firstWhere('name', 'Kategória');
        $categoryTitle = is_array($categoryTitle) ? Arr::get($categoryTitle, 'value') : null;

        if (! $categoryTitle) {
            $this->fail('No category specified.');
        }

        $category = Category::query()->firstWhere('title', $categoryTitle) ?: Category::create([
            'title' => $categoryTitle,
            'slug' => Str::slug($categoryTitle),
        ]);

        $author = null;

        $storeImageAsset = function (string $name, string $folder) use ($dir) {
            $path = "{$dir}/assets/{$name}";

            if (! File::exists($path)) {
                return null;
            }

            $dest = storage_path("app/public/{$folder}");
            File::ensureDirectoryExists($dest);

            $ext = match (File::mimeType($path)) {
                'image/jpeg' => 'jpeg',
                'image/png' => 'png'
            };

            $fileName = Str::random(32).'.'.$ext;

            File::copy($path, "{$dest}/$fileName");

            return "{$folder}/{$fileName}";
        };

        if ($authorSource = Arr::get($manifest, 'author')) {
            $name = Arr::get($authorSource, 'name');
            if (! $name) {
                $this->fail('Author name could not be resolved');
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
        }

        $resolveVideoPath = function (string $id) use ($dir) {
            $path = "{$dir}/videos/$id";

            if (File::exists("{$path}.mp4")) {
                return $path.'.mp4';
            } elseif (File::exists("{$path}.webm")) {
                $this->fail('This video is webm type. Might be an issue tho');
            }

            $this->fail("Unable to find video file for id {$id}");
        };

        $trailerPath = null;
        if ($trailerVideoId = Arr::get($manifest, 'preview_video_id')) {
            $trailerPath = $resolveVideoPath($trailerVideoId);
        }

        $uploadVideo = function (string $path) {
            $ext = File::extension($path);
            $name = Str::random(32).".{$ext}";
            $dir = storage_path('app/public/course-videos');
            File::ensureDirectoryExists($dir);
            $dest = "{$dir}/{$name}";

            File::copy($path, $dest);

            return Video::create([
                'file_path' => "course-videos/{$name}",
            ]);
        };

        $trailerVideo = $trailerPath ? $uploadVideo($trailerPath) : null;

        /** @var Course $course */
        $course = Course::create([
            'status' => CourseStatus::Draft,
            'author_id' => $author->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => Arr::get($manifest, 'description'),
            'category_id' => $category->id,
            'trailer_id' => $trailerVideo?->id,
            'cover_image_file_path' => $storeImageAsset('cover_full', 'course-covers') ?: $storeImageAsset('cover', 'course-covers'),
        ]);

        $resources = collect(Arr::get($manifest, 'attachments', []))->mapWithKeys(function (array $attachment) use ($course, $dir) {
            $destDir = storage_path('app/public/course-resources');
            File::ensureDirectoryExists($destDir);

            $path = "{$dir}/attachments/{$attachment['id']}";

            if (! File::exists($path)) {
                $this->fail("Attachment {$attachment['id']} does not exist.");
            }

            $mime = File::mimeType($path);
            $size = File::size($path);

            $name = Str::random(32);

            File::copy($path, "$destDir/{$name}");

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
                    $path = $resolveVideoPath($videoId);

                    $video = $uploadVideo($path);
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

        return Command::SUCCESS;
    }
}

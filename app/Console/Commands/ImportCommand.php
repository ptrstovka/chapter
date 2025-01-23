<?php

namespace App\Console\Commands;

use App\Enums\CourseStatus;
use App\Enums\VideoStatus;
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

class ImportCommand extends Command
{
    protected $signature = 'import {dir}';

    protected $description = 'Import course';

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
            $this->fail("The manifest file does not exist.");
        }

        $manifest = File::json($manifestPath);

        $title = Arr::get($manifest, 'title');

        if (! $title) {
            $this->fail("The course does not have a title.");
        }

        $chapters = collect(Arr::get($manifest, 'chapters', []));
        if ($chapters->isEmpty()) {
            $this->fail("No chapters in the course.");
        }

        $categoryTitle = collect(Arr::get($manifest, 'stats', []))->firstWhere('name', 'Kategória');
        $categoryTitle = is_array($categoryTitle) ? Arr::get($categoryTitle, 'value') : null;

        if(!$categoryTitle) {
            $this->fail("No category specified.");
        }

        $category = Category::query()->firstWhere('title', $categoryTitle) ?: Category::create([
            'title' => $categoryTitle,
        ]);

        // TODO: Autor nam chyba
        $author = Author::query()->firstOrFail();

        $resolveVideoPath = function (string $id) use ($dir) {
            $path = "{$dir}/videos/$id";

            if (File::exists("{$path}.mp4")) {
                return $path.'.mp4';
            } else if (File::exists("{$path}.webm")) {
                $this->fail("This video is webm type. Might be an issue tho");
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
            $dir = storage_path("app/public/course-videos");
            File::ensureDirectoryExists($dir);
            $dest = "{$dir}/{$name}";

            File::copy($path, $dest);

            return Video::create([
                'status' => VideoStatus::Pending,
                'file_path' => "course-videos/{$name}",
            ]);
        };

        $trailerVideo = $trailerPath ? $uploadVideo($trailerPath) : null;

        /** @var Course $course */
        $course = Course::create([
            'status' => CourseStatus::Draft,
            'author_id' => $author->id,
            'title' => $title,
            'description' => Arr::get($manifest, 'description'),
            'category_id' => $category->id,
            'trailer_id' => $trailerVideo?->id,
        ]);

        $resources = collect(Arr::get($manifest, 'attachments', []))->mapWithKeys(function (array $attachment) use ($course, $dir) {
            $destDir = storage_path("app/public/course-resources");
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

        // TODO: Cover Image

        $chapters->sortBy('position')->values()->each(function (array $chapterSource, int $idx) use ($course, $resolveVideoPath, $uploadVideo, $resources) {
            $title = Arr::get($chapterSource, 'title');

            $chapter = Chapter::create([
                'title' => $title,
                'position' => $idx + 1,
                'course_id' => $course->id,
            ]);

            collect(Arr::get($chapterSource, 'episodes', []))->sortBy('position')->values()->each(function (array $episodeSource, int $idx) use ($chapter, $resolveVideoPath, $uploadVideo, $resources) {
                $video = null;
                if ($videoId = Arr::get($episodeSource, 'video_id')) {
                    $path = $resolveVideoPath($videoId);

                    $video = $uploadVideo($path);
                }

                /** @var Lesson $lesson */
                $lesson = Lesson::create([
                    'title' => Arr::get($episodeSource, 'title'),
                    'description' => Arr::get($episodeSource, 'description'),
                    'position' => $idx + 1,
                    'chapter_id' => $chapter->id,
                    'video_id' => $video?->id,
                ]);

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

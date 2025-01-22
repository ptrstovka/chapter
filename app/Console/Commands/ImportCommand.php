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
            $id = Str::uuid()->toString();
            $name = "{$id}.{$ext}";
            $dir = storage_path("app/public/videos");
            File::ensureDirectoryExists($dir);
            $dest = "{$dir}/{$name}";

            File::copy($path, $dest);

            return Video::create([
                'status' => VideoStatus::Pending,
                'file_path' => "videos/{$name}",
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

        // TODO: Cover Image

        $chapters->sortBy('position')->values()->each(function (array $chapterSource, int $idx) use ($course, $resolveVideoPath, $uploadVideo) {
            $title = Arr::get($chapterSource, 'title');

            $chapter = Chapter::create([
                'title' => $title,
                'position' => $idx + 1,
                'course_id' => $course->id,
            ]);

            collect(Arr::get($chapterSource, 'episodes', []))->sortBy('position')->values()->each(function (array $episodeSource, int $idx) use ($chapter, $resolveVideoPath, $uploadVideo) {
                $video = null;
                if ($videoId = Arr::get($episodeSource, 'video_id')) {
                    $path = $resolveVideoPath($videoId);

                    $video = $uploadVideo($path);
                }

                $lesson = Lesson::create([
                    'title' => Arr::get($episodeSource, 'title'),
                    'description' => Arr::get($episodeSource, 'description'),
                    'position' => $idx + 1,
                    'chapter_id' => $chapter->id,
                    'video_id' => $video?->id,
                ]);

                // TODO: Prilohy
            });
        });

        return Command::SUCCESS;
    }
}

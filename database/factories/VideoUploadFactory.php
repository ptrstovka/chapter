<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Models\VideoUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VideoUpload> */
class VideoUploadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->for(Author::factory())->for(Category::factory()),
            'filename' => 'video.mp4',
            'size' => 1024,
            'expires_at' => now()->addDay(),
        ];
    }
}

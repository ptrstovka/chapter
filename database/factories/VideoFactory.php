<?php

namespace Database\Factories;

use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Video> */
class VideoFactory extends Factory
{
    public function definition(): array
    {
        return ['file_path' => 'course-videos/'.fake()->uuid().'.mp4'];
    }

    public function ready(): static
    {
        return $this->state(fn () => [
            'poster_image_file_path' => 'video-posters/'.fake()->uuid().'.jpg',
            'duration_seconds' => 30,
            'processing_status' => VideoProcessingStatus::Ready,
        ]);
    }
}

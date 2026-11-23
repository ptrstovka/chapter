<?php

namespace App\Http\Controllers\Studio;

use App\Models\Course;
use App\Models\VideoUpload;
use App\Support\VideoUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoUploadController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['active' => ['sometimes', 'array', 'max:100'], 'active.*' => ['uuid']]);
        VideoUpload::query()->where('user_id', $request->user()->id)->pending()
            ->whereIn('uuid', $validated['active'] ?? [])
            ->update(['expires_at' => now()->addHours(config('video.upload_retention_hours'))]);

        return response()->json([
            'uploads' => VideoUpload::query()->where('user_id', $request->user()->id)
                ->where('expires_at', '>', now())->whereHas('course')
                ->with(['course', 'lesson'])->latest()->limit(100)->get()->map->toUpload(),
        ]);
    }

    public function store(Request $request, Course $course, VideoUploads $uploads): JsonResponse
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
            'lesson_id' => ['nullable', 'uuid'],
            'filename' => ['required', 'string', 'max:255', 'regex:/\.(mp4|mov)$/i'],
            'size' => ['required', 'integer', 'min:1', 'max:'.($request->filled('lesson_id') ? 2 * 1024 ** 3 : 256 * 1024 ** 2)],
        ]);
        $lesson = ! empty($validated['lesson_id']) ? $course->lessons()->where('uuid', $validated['lesson_id'])->firstOrFail() : null;
        $upload = $uploads->register($course, $lesson, $request->user(), $validated['uuid'], $validated['filename'], $validated['size']);

        return response()->json($upload->toUpload(), 201);
    }

    public function show(Request $request, VideoUpload $videoUpload, VideoUploads $uploads): JsonResponse
    {
        $uploads->authorize($videoUpload, $request->user());

        return response()->json($videoUpload->toUpload());
    }

    public function destroy(Request $request, Course $course, string $uploadId, VideoUploads $uploads): JsonResponse
    {
        validator(['uuid' => $uploadId], ['uuid' => ['required', 'uuid']])->validate();
        $validated = $request->validate([
            'lesson_id' => ['nullable', 'uuid'],
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        $lesson = ! empty($validated['lesson_id']) ? $course->lessons()->where('uuid', $validated['lesson_id'])->firstOrFail() : null;
        $upload = $uploads->cancel($course, $lesson, $request->user(), $uploadId, $validated['filename'], $validated['size']);

        return response()->json($upload->toUpload());
    }
}

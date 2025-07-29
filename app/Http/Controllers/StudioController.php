<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Http\Request;

class StudioController extends Controller
{
    public function index() {
        return Inertia::render('Studio/StudioPage');
    }

    public function create() {
        $course = Course::create([
            'slug' => Str::slug('Adrianov kurz') . '-' . Str::random(5),
            'uuid' => Str::uuid(),
            'status' => CourseStatus::Published,
            'author_id' => 1,
            'category_id' => 1
        ]);

        return redirect()->route('studio.course.edit', ['id' => $course->id]);
    }

    public function edit($id) {
        $course = Course::with('author')->findOrFail($id);

        return Inertia::render('Studio/CourseEdit', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description, 
                'slug' => $course->slug,
                'cover_image_file_path' => $course->cover_image_file_path,
            ]
        ]);
    }

    public function update(Request $request, $id) {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'string|required|min:1|max:255',
            'description' => 'string|nullable|min:1|max:1000',
            'video_path' => 'string|required',
            'cover_image_file_path' => 'nullable|string'
        ]);

        $trailer = Video::create([
            'file_path' => $request->input('video_path')
        ]);

        $course->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'trailer_id' => $trailer->id,
            'cover_image_file_path' => $request->input('cover_image_file_path')
        ]);


        return redirect()->back()->with('success', 'Course updated.');
    }

    public function uploadCover(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->store('assets', 'public');

        return response()->json([
            'path' => Storage::url($path),
        ]);
    }

    public function uploadVideo(Request $request) {
        $request->validate([
            'file' => 'required|mimetypes:video/mp4,video/webm,video/oog,video/mov|max:51200',
        ]);

        $file = $request->file('file');
        $path = $file->store('course-videos', 'public');

        return response()->json([
            'path' => Storage::url($path),
        ]);
    }

}

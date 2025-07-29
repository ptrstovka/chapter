<?php

namespace App\Http\Controllers;

use App\Enums\CourseStatus;
use App\Models\Course;
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
            'title' => 'Adrianov kurz',
            'slug' => Str::slug('Adrianov kurz') . '-' . Str::random(5),
            'uuid' => Str::uuid(),
            'status' => CourseStatus::Published,
            'author_id' => 1,
            'category_id' => 1
        ]);

        // Presmeruj rovno na editáciu nového kurzu
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
            'title' => 'string|required',
            'description' => 'string|required'
        ]);

        $course->update($validated);

        return redirect()->back()->with('success', 'Course updated.');
    }
}

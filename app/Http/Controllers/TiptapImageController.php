<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TiptapImageController
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                Rule::imageFile()
                    ->max(64 * 1024)
                    ->extensions(['jpg', 'png', 'jpeg']),
                'mimes:jpg,png,jpeg',
            ],
        ]);

        $file = $request->file('file');

        $extension = $file->extension();

        if (! $extension) {
            throw ValidationException::withMessages([
                'file' => 'Unable to guess file extension.',
            ]);
        }

        $name = Str::random(40);

        $path = $file->storePubliclyAs('tiptap-images', $name.'.'.$extension, ['disk' => 'public']);

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}

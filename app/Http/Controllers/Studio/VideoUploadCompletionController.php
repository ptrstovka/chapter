<?php

namespace App\Http\Controllers\Studio;

use App\Models\VideoUpload;
use App\Support\VideoUploads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoUploadCompletionController
{
    public function __invoke(Request $request, VideoUpload $videoUpload, VideoUploads $uploads): JsonResponse
    {
        $uploads->authorize($videoUpload, $request->user());

        return response()->json($uploads->complete($videoUpload)->toUpload());
    }
}

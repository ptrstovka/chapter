<?php

namespace App\Support;

use App\Enums\Preference;
use App\Facades\Settings;

class VideoSettings
{
    /** @return array{hls: bool, keep_original: bool, qualities: array<int, int>} */
    public function snapshot(): array
    {
        Settings::clearMemory();

        return [
            'hls' => Settings::boolean(Preference::EnableVideoHls),
            'keep_original' => Settings::boolean(Preference::KeepOriginalVideos),
            'qualities' => array_map('intval', Settings::get(Preference::VideoQualities)),
        ];
    }
}

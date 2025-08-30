<?php

namespace App\View\Models;

use App\Models\Video;
use StackTrace\Ui\ViewModel;

class VideoSource extends ViewModel
{
    public function __construct(
        protected string $url,
        protected ?string $posterImageUrl,
    ) {}

    public function toView(): array
    {
        return [
            'url' => $this->url,
            'posterImageUrl' => $this->posterImageUrl,
        ];
    }

    /**
     * Create new video source for given video.
     */
    public static function for(?Video $video): ?static
    {
        if ($url = $video?->getUrl()) {
            return new static(
                url: $url,
                posterImageUrl: $video->getPosterImageUrl(),
            );
        }

        return null;
    }
}

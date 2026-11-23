<?php

namespace App\View\Models;

use App\Models\Video;
use StackTrace\Ui\ViewModel;

class VideoSource extends ViewModel
{
    public function __construct(
        protected int $id,
        protected ?string $url,
        protected ?string $posterImageUrl,
        protected string $type,
        protected string $status,
    ) {}

    public function toView(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'posterImageUrl' => $this->posterImageUrl,
            'type' => $this->type,
            'status' => $this->status,
        ];
    }

    /**
     * Create new video source for given video.
     */
    public static function for(?Video $video): ?static
    {
        if ($video) {
            return new static(
                id: $video->id,
                url: $video->getUrl(),
                posterImageUrl: $video->getPosterImageUrl(),
                type: $video->getMimeType(),
                status: $video->processing_status->value,
            );
        }

        return null;
    }
}

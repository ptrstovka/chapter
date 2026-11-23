<?php

namespace App\Table\Actions;

use App\Models\Video;
use App\Support\VideoProcessing;
use App\Support\VideoSettings;
use Illuminate\Support\Facades\Gate;
use StackTrace\Ui\Selection;
use StackTrace\Ui\Table\Actions\Action;

class ConvertVideoToHlsAction extends Action
{
    public function getLabel(): ?string
    {
        return __('Convert to HLS');
    }

    protected function getTitle(): ?string
    {
        return __('Convert to HLS');
    }

    protected function getDescription(): ?string
    {
        return __('Convert the selected videos to HLS using the current video settings? The videos will be unavailable until processing finishes.');
    }

    protected function getConfirmLabel(): string
    {
        return __('Convert');
    }

    public function authorize(): bool
    {
        return Gate::allows('viewAdmin') && app(VideoSettings::class)->snapshot()['hls'];
    }

    public function handle(Selection $selection, VideoProcessing $processing, VideoSettings $settings): void
    {
        $options = $settings->snapshot();
        abort_unless($options['hls'], 403);

        Video::query()->whereKey($selection->all())->eachById(function (Video $video) use ($processing, $options) {
            $processing->queueHlsConversion($video, $options);
        });
    }
}

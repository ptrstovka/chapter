<?php

namespace App\Table\Actions;

use App\Models\Video;
use App\Support\VideoMetadata;
use Illuminate\Support\Facades\Gate;
use StackTrace\Ui\Facades\Toast;
use StackTrace\Ui\Selection;
use StackTrace\Ui\Table\Actions\Action;
use Throwable;

class RefreshVideoMetadataAction extends Action
{
    public function getLabel(): ?string
    {
        return __('Refresh metadata');
    }

    protected function getTitle(): ?string
    {
        return __('Refresh metadata');
    }

    protected function getDescription(): ?string
    {
        return __('Refresh the sizes of originals and HLS streams for the selected videos?');
    }

    protected function getConfirmLabel(): string
    {
        return __('Refresh');
    }

    public function authorize(): bool
    {
        return Gate::allows('viewAdmin');
    }

    public function handle(Selection $selection, VideoMetadata $metadata): void
    {
        $failed = [];

        Video::query()->whereKey($selection->all())->eachById(function (Video $video) use ($metadata, &$failed) {
            try {
                $metadata->refresh($video);
            } catch (Throwable $exception) {
                report($exception);
                $failed[] = $video->id;
            }
        });

        $summary = $failed ? __('Failed video IDs: :ids', ['ids' => implode(', ', $failed)]) : null;

        Toast::make(__('Video metadata refresh finished.'), $summary, $failed ? 'destructive' : 'default');
    }
}

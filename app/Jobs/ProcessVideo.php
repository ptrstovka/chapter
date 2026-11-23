<?php

namespace App\Jobs;

use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use App\Support\VideoProcessing;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 180;

    public int $timeout;

    public bool $failOnTimeout = true;

    public function __construct(public int $videoId, public string $attempt)
    {
        $this->timeout = (int) config('video.timeout');
        $this->onConnection(config('video.queue_connection'))->onQueue(config('video.queue'));
    }

    public function handle(VideoProcessing $processing): void
    {
        try {
            $processing->process($this->videoId, $this->attempt, markFailed: false);
        } catch (LockTimeoutException $exception) {
            $this->release(180);
        } catch (Throwable $exception) {
            if ($this->attempts() < $this->tries) {
                Video::query()->whereKey($this->videoId)->where('processing_attempt', $this->attempt)
                    ->where('processing_status', '!=', VideoProcessingStatus::Ready)
                    ->update(['processing_status' => VideoProcessingStatus::Queued]);
            } else {
                $processing->fail($this->videoId, $this->attempt, $exception);
            }
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            app(VideoProcessing::class)->fail($this->videoId, $this->attempt, $exception);
        }
    }
}

<?php

namespace App\Support;

use FFMpeg\Format\Video\X264;
use Illuminate\Filesystem\FilesystemAdapter;
use ProtoneMedia\LaravelFFMpeg\Exporters\HLSVideoFilters;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use ProtoneMedia\LaravelFFMpeg\Support\StreamParser;
use RuntimeException;

class VideoEncoder
{
    /**
     * @param  array{hls: bool, keep_original: bool, qualities: array<int, int>}  $options
     * @return array{duration: int, poster: string, playlist: string|null}
     */
    public function encode(FilesystemAdapter $work, string $source, array $options): array
    {
        $media = FFMpeg::fromDisk($work)->open($source);
        try {
            $stream = $media->getVideoStream();
            if (! $stream) {
                throw new RuntimeException('The file does not contain a video stream.');
            }
            $duration = (int) ceil($media->getDurationInSeconds());
            if ($duration <= 0) {
                throw new RuntimeException('The video duration is invalid.');
            }

            $width = (int) $stream->get('width');
            $height = (int) $stream->get('height');
            $rotation = (int) data_get($stream->get('tags', []), 'rotate', 0);
            foreach ($stream->get('side_data_list', []) as $data) {
                $rotation = (int) ($data['rotation'] ?? $rotation);
            }
            if (abs($rotation) % 180 === 90) {
                [$width, $height] = [$height, $width];
            }
            if ($width < 2 || $height < 2) {
                throw new RuntimeException('The video dimensions are invalid.');
            }

            FFMpeg::fromDisk($work)->open($source)->getFrameFromSeconds(0)
                ->export()->toDisk($work)->save('poster.jpg');

            if ($options['hls']) {
                $work->makeDirectory('output');
                $seconds = (int) config('video.segment_seconds');
                $fps = (float) (StreamParser::new($stream)->getFrameRate() ?: 30);
                $export = FFMpeg::fromDisk($work)->open($source)->exportForHLS()
                    ->setSegmentLength($seconds)->setKeyFrameInterval(max(2, (int) round($fps * $seconds)))->toDisk($work);

                foreach ($this->renditions($width, $height, $options['qualities']) as $rendition) {
                    $format = (new X264('aac', 'libx264'))->setPasses(1)
                        ->setKiloBitrate($rendition['bitrate'])->setAudioKiloBitrate(128)->setAudioChannels(2)
                        ->setAdditionalParameters([
                            '-preset', 'fast', '-pix_fmt', 'yuv420p', '-force_key_frames', 'expr:gte(t,n_forced*'.$seconds.')',
                            '-map_metadata', '-1', '-sn', '-dn',
                        ]);
                    $export->addFormat($format, function (HLSVideoFilters $filters) use ($rendition) {
                        $filters->addFilter('scale='.$rendition['width'].':'.$rendition['height'].',setsar=1');
                    });
                }
                $export->save('output/master.m3u8');
            }

            return ['duration' => $duration, 'poster' => 'poster.jpg', 'playlist' => $options['hls'] ? 'output/master.m3u8' : null];
        } finally {
            $media->cleanupTemporaryFiles();
        }
    }

    /**
     * @param  array<int, int>  $qualities
     * @return array<int, array{width: int, height: int, bitrate: int}>
     */
    public function renditions(int $width, int $height, array $qualities): array
    {
        $shortSide = min($width, $height);
        $selected = array_values(array_unique(array_filter($qualities, fn (int $quality) => $quality <= $shortSide)));
        sort($selected);
        if ($selected === []) {
            $selected = [$shortSide];
        }

        return array_map(function (int $quality) use ($width, $height, $shortSide) {
            $scale = min(1, $quality / $shortSide);
            $bitrates = config('video.bitrates');
            $bitrate = end($bitrates);
            foreach ($bitrates as $resolution => $value) {
                if ($quality <= $resolution) {
                    $bitrate = $value;
                    break;
                }
            }

            return [
                'width' => max(2, (int) floor($width * $scale / 2) * 2),
                'height' => max(2, (int) floor($height * $scale / 2) * 2),
                'bitrate' => $bitrate,
            ];
        }, $selected);
    }
}

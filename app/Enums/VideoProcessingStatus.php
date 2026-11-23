<?php

namespace App\Enums;

enum VideoProcessingStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}

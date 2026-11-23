<?php

namespace App\Enums;

enum VideoUploadStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}

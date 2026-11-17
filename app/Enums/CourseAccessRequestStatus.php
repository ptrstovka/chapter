<?php

namespace App\Enums;

enum CourseAccessRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Denied = 'denied';
}

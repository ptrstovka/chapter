<?php

namespace App\Enums;

enum CourseAccessMode: string
{
    case Public = 'public';
    case RequestOnly = 'request-only';
    case Private = 'private';
}

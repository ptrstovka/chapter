<?php


namespace App\Enums;


enum CourseStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Published = 'published';
    case Unpublished = 'unpublished';
}

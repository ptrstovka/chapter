<?php


namespace App\Enums;


enum CourseStatus: string
{
    case Draft = 'draft';
    case Publishing = 'publishing';
    case PublishFailure = 'publish-failure';
    case Published = 'published';
    case Unpublished = 'unpublished';
}

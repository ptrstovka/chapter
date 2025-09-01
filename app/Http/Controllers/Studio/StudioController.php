<?php


namespace App\Http\Controllers\Studio;


class StudioController
{
    public function __invoke()
    {
        // return to_route('studio.overview');
        return to_route('studio.courses');
    }
}

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();

if (config('horizon.enabled')) {
    Schedule::command('horizon:snapshot')->everyFiveMinutes();
}

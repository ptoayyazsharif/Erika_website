<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * The daily reminder, hourly.
 *
 * Hourly is not a mistake: each push subscription carries the timezone its
 * browser reported, and the command sends only to devices for which it is now
 * the chosen hour locally. A daily schedule would send at one hour for the
 * whole world.
 *
 * withoutOverlapping because a slow push service must not let a second copy
 * start beside the first and send everything twice.
 */
Schedule::command('escalate:remind')->hourly()->withoutOverlapping();

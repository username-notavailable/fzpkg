<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('app:optimize', function () {
    $this->call('app:clear');
    $this->call('config:cache');
    $this->call('event:cache');
    $this->call('route:cache');
});

Artisan::command('app:clear', function () {
    $this->call('cache:clear');
    $this->call('clear-compiled');
    $this->call('config:clear');
    $this->call('event:clear');
    $this->call('route:clear');
    $this->call('view:clear');
});

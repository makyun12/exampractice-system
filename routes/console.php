<?php

use App\Services\AttemptService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('practice:expire', function () {
    app(AttemptService::class)->expire();
    $this->info('Expired practice sessions finalized.');
})->purpose('Finalize all practice attempts that have reached their deadline');
Schedule::command('practice:expire')->everyMinute()->withoutOverlapping();

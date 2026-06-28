<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('energy:summarize')->monthlyOn(1, '02:00');
Schedule::command('energy:check-thresholds')->dailyAt('08:00');
Schedule::command('telegram:send-alerts')->dailyAt('09:00');
Schedule::command('telegram:weekly-summary')->weeklyOn(7, '20:00'); // Tối Chủ Nhật hàng tuần lúc 20:00

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crm:prune-expired-challenges')->daily()->withoutOverlapping();

Schedule::command('crm:backup')
    ->dailyAt(config('crm.backup.schedule_time', '20:00'))
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

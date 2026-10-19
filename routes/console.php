<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crm:prune-expired-challenges')->daily()->withoutOverlapping();

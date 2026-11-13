<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('outage:expire-reports')->everyMinute();

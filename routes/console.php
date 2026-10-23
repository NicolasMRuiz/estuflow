<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('m04:tick')->everyMinute()->withoutOverlapping(3);

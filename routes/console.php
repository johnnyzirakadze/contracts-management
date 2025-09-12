<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('contracts:send-reminders')
	->dailyAt('09:00')
	->timezone('Asia/Tbilisi');

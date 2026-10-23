<?php

return [
    'timezone' => 'America/Argentina/Buenos_Aires',
    'reminder_email_daily_limit' => (int) env('REMINDER_EMAIL_DAILY_LIMIT', 250),
    'http_timeout' => 8,
    'http_max_bytes' => 2_000_000,
];

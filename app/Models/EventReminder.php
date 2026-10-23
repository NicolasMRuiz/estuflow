<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable $useful_until
 */
class EventReminder extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['due_at' => 'immutable_datetime', 'useful_until' => 'immutable_datetime', 'email_claimed_at' => 'immutable_datetime', 'email_sent_at' => 'immutable_datetime'];
    }
}

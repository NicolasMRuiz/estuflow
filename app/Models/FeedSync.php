<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $last_attempt_at
 * @property CarbonImmutable|null $last_success_at
 * @property CarbonImmutable|null $next_attempt_at
 * @property CarbonImmutable|null $leased_until
 */
class FeedSync extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['last_attempt_at' => 'immutable_datetime', 'last_success_at' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime', 'leased_until' => 'immutable_datetime'];
    }
}

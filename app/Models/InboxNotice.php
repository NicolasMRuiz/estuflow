<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboxNotice extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['read_at' => 'immutable_datetime'];
    }
}

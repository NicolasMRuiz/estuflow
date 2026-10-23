<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property array<int, string> $attachments */
class BoardPost extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['published_on' => 'immutable_date', 'attachments' => 'array', 'detected_at' => 'immutable_datetime'];
    }
}

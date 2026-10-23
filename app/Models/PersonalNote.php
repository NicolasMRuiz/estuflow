<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalNote extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [];
    }
}

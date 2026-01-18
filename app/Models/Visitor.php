<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    protected $guarded = [];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim( "{$this->first_name} {$this->middle_part} {$this->last_name}" ),
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Worker extends Model
{
    use HasFactory;

    public function workdays()
    {
        return $this->hasMany(Workday::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}

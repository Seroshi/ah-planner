<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];

    public function workday()
    {
        // To recognize the workday_id column 
        return $this->belongsTo(Workday::class);
    }

    public function shiftSwap()
    {
        // Making it linked with one Shiftswap record
        return $this->hasOne(ShiftSwap::class);
    }

    public function worker()
    {
        // To recognize the worker_id column 
        return $this->belongsTo(Worker::class);
    }

    protected static function booted()
    {
        static::created(function ($message) {
            // Delay the job by 2 minutes
            \App\Jobs\ReplyToMessage::dispatch($message)->delay(now()->addSeconds(8));
        });
    }
}

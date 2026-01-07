<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function workday()
    {
        return $this->belongsTo(Workday::class);
    }

    public function worker()
    {
        // Look at 'worker_id' field that match with ID from Worker model 
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

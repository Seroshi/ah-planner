<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftSwap extends Model
{
    protected $guarded = [];
    
    public function message()
    {
        // To recognize the message_id column
        return $this->belongsTo(Message::class);
    }
}

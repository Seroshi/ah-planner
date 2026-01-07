<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Workday extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'remark_time' => 'datetime',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public static function getTopicOptions(): array
    {
        return [
            '1' => [
            'value' => 'Wijziging indienen',
            'label' => 'Shift wijziging',
            ],
            '2' => [
                'value' => 'Vraag stellen',
                'label' => 'Shift vraag',
            ],
            '3' => [
                'value' => 'Opmerking geven',
                'label' => 'Shift opmerking',
            ],
        ];
    }

    public function worker(): BelongsTo
    {
        // Look at 'worker_id' field that match with ID from Worker model 
        return $this->belongsTo(Worker::class);
    }
}

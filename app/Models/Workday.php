<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
                'value'         => 'Wijziging indienen',
                'label'         => 'Shift wijziging',
            ],
            '2' => [
                'value'         => 'Vraag stellen',
                'label'         => 'Shift vraag',
            ],
            '3' => [
                'value'         => 'Opmerking geven',
                'label'         => 'Shift opmerking',
            ],
            '4' => [
                'value'         => 'Shift ruil',
                'label'         => 'Shift ruil',
                'description'   => 'Verzoek verstuurd naar desbetreffende',
            ],
        ];
    }

    public static function getBreakTime($hours)
    {
        $breakTime = '0 min';
        if($hours >= 4 && $hours < 6) $breakTime = "15 min";
        elseif($hours >= 6 && $hours <= 7) $breakTime = "30 min";
        elseif($hours > 7) $breakTime = "1 u.";

        return $breakTime;
    }

    public static function swapAllowedInTime($date)
    {
        $diffDays = Carbon::now()->diffInDays($date);
        return ($diffDays >= 2) ? true : false;
    }

    public function worker(): BelongsTo
    {
        // Look at 'worker_id' field that match with ID from Worker model 
        return $this->belongsTo(Worker::class);
    }
}

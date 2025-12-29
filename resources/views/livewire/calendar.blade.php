<?php

use function Livewire\Volt\{state, computed};
use App\Models\Workday;
use Carbon\Carbon;

// Define state
state([
    'startsAt' => Carbon::now(),
    'selectedDate' => fn() => now()->toDateString(), // Track selection by YYYY-MM-DD
    'view' => 'month', // 'month' or 'week',,
]);

$dbConnection = computed(function(){
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        // Database is down! Redirect or show a custom view
        return "error";
    }
});

$weekNumber = computed(function () {
    return Carbon::parse($this->selectedDate)->weekOfYear;
});

// Action to switch views
$setView = fn($view) => $this->view = $view;


$selectDate = function ($dateString) {
    $this->selectedDate = $dateString;
    $this->startsAt = Carbon::parse($dateString);
};

$nextDate = function () {
    if ($this->view === 'month') {
        // Move to the next month AND snap to the 1st
        $this->startsAt = $this->startsAt->copy()->addMonth()->startOfMonth();
    } else {
        // Just move exactly 7 days forward
        $this->startsAt = $this->startsAt->copy()->addWeek();
    }
};

$prevDate = function () {
    if ($this->view === 'month') {
        // Move to the previous month AND snap to the 1st
        $this->startsAt = $this->startsAt->copy()->subMonth()->startOfMonth();
    } else {
        // Just move exactly 7 days backward
        $this->startsAt = $this->startsAt->copy()->subWeek();
    }
};

$calendarGrid = computed(function () {
    $days = [];

    if ($this->view === 'month') {
        // Find the start of the month for the date currently in focus
        $focus = $this->startsAt->copy()->startOfMonth();
        $start = $focus->copy()->startOfWeek(Carbon::MONDAY); // NL / ISO Style
        $end = $focus->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
    } else {
        // Week view: Just the week containing the focal date
        $start = $focus->copy()->startOfWeek(Carbon::MONDAY); // NL / ISO Style
        $end = $this->startsAt->copy()->endOfWeek(Carbon::SATURDAY);
    }

    $currentDay = $start->copy();

    while ($currentDay <= $end) {
        $days[] = [
            'date' => $currentDay->copy(),
            'isCurrentMonth' => $currentDay->month === $this->startsAt->month,
            'isToday' => $currentDay->isToday(),
        ];
        $currentDay->addDay();
    }

    return $days;
});

$workdayData = computed(function () {
    try{
        // Fetch all workdays and turn them into a key-value array [ 'date' => [data] ]
        return Workday::all()->keyBy(function ($item) {
            return $item->date->format('Y-m-d');
        })->toArray();
    }
    catch (\Exception $e) { // Database is down!  
        report($e); 
        return [];
    }
});

// Get info from the selected day and it's corresponding week
$selectedWeekDays = computed(function () {
    try {
        $selectedDate = Carbon::parse($this->selectedDate);
        
        // 1. Calculate boundaries
        $startOfWeek = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $selectedDate->copy()->endOfWeek(Carbon::SUNDAY);

        // 2. Fetch only the records for this specific week from DB
        $weeklyRecords = Workday::whereBetween('date', [
                $startOfWeek->toDateString(), 
                $endOfWeek->toDateString()
            ])
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));

        // 3. Build the 7-day array for the UI
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $current = $startOfWeek->copy()->addDays($i);
            $dateStr = $current->toDateString();

            $days[] = [
                'date' => $current,
                'dateString' => $dateStr,
                'isToday' => $current->isToday(),
                'isSelected' => $this->selectedDate === $dateStr,
                // Check if our small DB result has a record for this day
                'info' => $weeklyRecords[$dateStr] ?? null,
            ];
        }

        return $days;
    }
    catch (\Exception $e) { //Database is down! 
        report($e); //Send error to logs 
        return []; 
    }
});

// Helper function to check the date string
$getDayInfo = function ($date) {
    return $this->workdayData[$date->toDateString()] ?? null;
};

?>

<div class="calendar">

    <!-- DB disconnected Card -->
    @if($this->dbConnection() === "error")
        <div role="alert mb-2">
            <div class="bg-red-500 text-white font-bold rounded-t px-4 py-2">
                DB Connection Error!
            </div>
            <div class="border border-t-0 border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700">
                <p>Lost connection with the database. Calendar won't work until connection is restored.</p>
            </div>
        </div>
    @endif

    <!-- Calendar Navigation -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-[#00A2E5]">
            {{ $this->startsAt->format('F Y') }}
            <div class="space-x-2">
                <button wire:click="prevDate">Prev</button>
                <button wire:click="nextDate">Next</button>
            </div>
        </h2>
    </div>

    <!-- Week/Month Toggle group -->
    <div class="inline-flex mb-4">
        <button wire:click="setView('week')"
        class="font-bold py-2 px-4 rounded-l transition-all {{ $this->view === 'week' ? 'bg-blue-400 shadow-sm text-white' : 'bg-[#EEEEEE] text-gray-400 hover:bg-blue-200 hover:text-gray-700' }}">
            week
        </button>
        
        <button wire:click="setView('month')"
        class="font-bold text-white py-2 px-4 rounded-r transition-all {{ $this->view === 'month' ? 'bg-blue-400 shadow-sm text-white' : 'bg-[#EEEEEE] text-gray-400 hover:bg-blue-200 hover:text-gray-700' }}">
            maand
        </button>
    </div>

    <!-- Days of the week-->
    <div class="text-[13px] bg-gray-200 grid grid-cols-7">
        <div class="p-2"><p>ma</p></div>
        <div class="p-2"><p>di</p></div>
        <div class="p-2"><p>wo</p></div>
        <div class="p-2"><p>do</p></div>
        <div class="p-2"><p>vr</p></div>
        <div class="p-2"><p>za</p></div>
        <div class="p-2"><p>zo</p></div>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-[#EEEEEE] grid grid-cols-7 mb-4">
        @foreach($this->calendarGrid as $day) 
            @php 
                $dateStr = $day['date']->toDateString();
                $isSelected = $this->selectedDate === $dateStr;
            @endphp
            <div wire:click="selectDate('{{ $dateStr }}')" class="p-2 cursor-pointer
                {{ $isSelected && !$day['isToday'] ? 'ring-2 ring-blue-400 ring-inset' : 'hover:bg-gray-100' }}
                {{ $day['isCurrentMonth'] ? '' : 'opacity-30' }}"
            >
                <div class="day font-bold flex justify-between items-start">
                    @if($day['isToday'])
                        <p class="font-bold today">{{ $day['date']->day }}</p>
                    @else
                        <p class="font-bold ">{{ $day['date']->day }}</p>
                    @endif

                    @php 
                        $info = $this->getDayInfo($day['date']); 
                    @endphp

                    @if($info)
                        <span class="text-[9px] px-1 rounded {{ $info['color'] ?? 'bg-gray-200' }}">
                            {{ $info['type'] }}
                        </span>
                    @endif
                </div>

                @if($info)
                    <div class="text-[10px] text-gray-600">
                        {{ $info['start_time'] }} - {{ $info['end_time'] }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="font-bold">Week {{ $this->weekNumber }}</div>
    <div>
        @foreach($this->selectedWeekDays as $day)
            <div>{{$day['date']->format('d-m')}}
                @if($day['date']->isPast() && $day['info']?->start_time && !$day['isToday'])
                    klaar: 
                @elseif($day['info']?->type)
                    gepland:
                @endif
                {{$day['info']?->start_time->format('H:i')}}-{{$day['info']?->end_time->format('H:i')}}
            </div>
        @endforeach
    </div>
</div>
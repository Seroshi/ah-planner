<?php

use function Livewire\Volt\{state, computed};
use App\Models\Workday;
use Carbon\Carbon;

// Define state
state([
    'startsAt' => Carbon::now(),
    'selectedDate' => fn() => now()->toDateString(), // Track selection by YYYY-MM-DD
    'selectedId' => 1,
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

//Capture click from the calendar day
$selectDate = function ($dateString) {
    $this->selectedDate = $dateString;
    $this->startsAt = Carbon::parse($dateString);
};

//Next month click button
$nextDate = function () {
    $this->startsAt = $this->startsAt->copy()->addMonth();
};

//Previous month click button
$prevDate = function () {
    $this->startsAt = $this->startsAt->copy()->subMonth();
};

//Defines the data for the calendar grid
$calendarGrid = computed(function () {
    $days = [];

    // Find the start of the month for the date currently in focus
    $focus = $this->startsAt->copy()->startOfMonth();
    $start = $focus->copy()->startOfWeek(Carbon::MONDAY); // NL / ISO Style
    $end = $focus->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
    
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

$workdayData = computed(function () 
{
    try{
        $getWorker = \App\Models\Worker::first();
        if($getWorker){
            // Fetch all workdays and turn them into a key-value array [ 'date' => [data] ]
            return Workday::where('worker_id', $getWorker->id)->get()->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            })->toArray();
        }else return abort(404, 'No record found in the database.');
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
        $getWorker = \App\Models\Worker::first();
        $weeklyRecords = Workday::where('worker_id', $getWorker->id)
            ->whereBetween('date', [
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

$getBreakTime = function ($hours){
    $breakTime = '0 min';
    if($hours >= 4 && $hours < 6) $breakTime = "15 min";
    elseif($hours >= 6 && $hours <= 7) $breakTime = "30 min";
    elseif($hours > 7) $breakTime = "1 u.";

    return $breakTime;
}

?>

<div class="calendar w-full mb-6" 
    x-data="{ showModal: false }" @close-modal.window="showModal = false"
>

    <!-- DB disconnected Card -->
    @if($this->dbConnection() === "error")
        <div role="alert" class="mb-4">
            <div class="bg-red-500 text-white font-bold rounded-t px-4 py-2">
                DB Connection Error!
            </div>
            <div class="border border-t-0 border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700">
                <p>Lost connection with the database. Calendar won't work until connection is restored.</p>
            </div>
        </div>
    @endif

    <!-- Calendar Navigation -->
    <div class="flex justify-center items-center mb-2">
        <button wire:click="prevDate" 
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-light px-2 rounded-full w-[35px] h-[35px]"
        >
            <span><i class="bi bi-chevron-left"></i></span>
        </button>
        <h3 class="text-xl font-bold mx-3">
            {{ $this->startsAt->format('F Y') }}
        </h3>
        <button wire:click="nextDate" 
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-2 rounded-full w-[35px] h-[35px]"
        >
            <span><i class="bi bi-chevron-right"></i></span>
        </button>
    </div>

    <!-- Days of the week-->
    <div class="text-[13px] bg-ah text-white grid grid-cols-7">
        <div class="p-2"><p>ma</p></div>
        <div class="p-2"><p>di</p></div>
        <div class="p-2"><p>wo</p></div>
        <div class="p-2"><p>do</p></div>
        <div class="p-2"><p>vr</p></div>
        <div class="p-2"><p>za</p></div>
        <div class="p-2"><p>zo</p></div>
    </div>

    <!-- Calendar Grid -->
    <div class="gray-light grid grid-cols-7 mb-6">
        @foreach($this->calendarGrid as $day) 
            @php 
                $dateStr = $day['date']->toDateString();
                $isSelected = $this->selectedDate === $dateStr;
            @endphp
            <div wire:click="selectDate('{{ $dateStr }}')" class="p-2 cursor-pointer gap-1
                {{ $isSelected && !$day['isToday'] ? 'ring-2 ring-blue-400 ring-inset' : 'hover-gray' }}
                {{ $day['isCurrentMonth'] ? '' : 'opacity-30' }}"
            >
                <div class="sm:text-[13px] font-bold flex sm:justify-between items-center items-start">
                    @if($day['isToday'])
                        <p class="font-bold today flex justify-center items-center mt-[-2px] ml-[-4px] sm:ml-[-6px] sm:mt-[-4px]">{{ $day['date']->day }}</p>
                    @else
                        <p class="font-bold ">{{ $day['date']->day }}</p>
                    @endif

                    @php 
                        $info = $this->getDayInfo($day['date']); 
                    @endphp

                    @if($info)
                        @if($info['type'] === 'work')
                            <span class="bg-blue-100 text-[9px] px-1 rounded hidden sm:block">
                                shift
                            </span>
                            <div class="dot sm:hidden bg-blue-400 ml-1"></div>
                        @elseif($info['type'] === 'holiday')
                            <span class="bg-orange-100 text-[9px] px-1 rounded hidden sm:block">
                                verlof
                            </span>
                            <div class="dot sm:hidden bg-yellow-500 ml-1"></div>
                        @elseif($info['type'] === 'sick')
                            <span class="bg-red-200 text-[9px] px-1 rounded hidden sm:block">
                                ziek
                            </span>
                            <div class="dot sm:hidden bg-red-500 ml-1"></div>
                        @endif
                    @endif
                </div>

                @if($info && $info['type'] != 'holiday')
                    <div class="text-[10px] text-gray-600 hidden sm:block">
                        {{ $info['start_time'] }} - {{ $info['end_time'] }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Week Information -->
    <section>
        <h4 class="text-xl font-bold">My Shifts: Week {{ $this->weekNumber }}</h4>
        <div class="mb-2 sm:text-[14px] ">
            @php
                $dayData = $this->selectedWeekDays;
                $firstDay = $dayData[0]['date'];
                $lastDay = $dayData[6]['date'];
            @endphp
            
            @if($dayData)
                @if($firstDay->format('m') == $lastDay->format('m'))
                    {{$firstDay->format('d')}} t/m
                    {{$lastDay->format('d')}}
                    <span>{{$firstDay->format('F')}}</span>
                @else
                    <span>{{$firstDay->format('d')}} {{$firstDay->format('F')}} t/m 
                        {{$lastDay->format('d')}} {{$lastDay->format('F')}}</span>
                @endif
            @endif
        </div>
        <div>
            @foreach($this->selectedWeekDays as $day)
                @php 
                    $hourDiff = $day['info']?->start_time?->diffInHours($day['info']?->end_time) ?? 0;
                @endphp
                @if($day['info']?->type)
                    <div class="sm:text-[14px] my-1 rounded
                        {{$day['isSelected'] ? 'ring-1 ring-blue-400 ring-outset' : ''}}"
                    >
                        <div class="gray-light rounded flex justify-between items-center p-2 overflow-x-auto">
                            <div>
                                @if($day['info']?->type)
                                    <div>{{$day['date']->format('D d M Y')}}</div>
                                    <div class="flex gap-1">
                                        @if($day['info']->type === 'work')
                                            <div class="flex-none" style="width: 130px;">
                                                @if($day['date']->isPast() && !$day['isToday'])
                                                    <i class="bi bi-check-circle text-blue-400"></i>
                                                @else
                                                    <i class="bi bi-clock text-blue-400"></i>
                                                @endif
                                                <span class="font-light">
                                                    {{$day['info']?->start_time->format('H:i')}} - {{$day['info']?->end_time->format('H:i')}}
                                                </span>
                                            </div>
                                            <div class="flex-none" style="width: 90px;">
                                                <i class="bi bi-clock-history text-blue-400"></i>
                                                <span class="font-light">
                                                    {{ $hourDiff }} u.
                                                </span>
                                            </div>
                                            <div class="flex-none" style="width: 100px;">
                                                <i class="bi bi-cup-hot text-blue-400"></i>
                                                <span class="font-light">{{$this->getBreakTime($hourDiff)}}</span>
                                            </div>
                                            <div>
                                                <i class="bi bi-tags text-blue-400"></i>
                                                <span class="font-light">vullen</span>
                                            </div>
                                        @elseif($day['info']->type === 'holiday')
                                            <i class="bi bi-brightness-alt-high-fill text-blue-400"></i>
                                            <span class="font-light">vrij</span>
                                        @elseif($day['info']->type === 'sick')
                                            <i class="bi bi-info-circle text-orange-500"></i>
                                            <span class="font-light">{{$day['info']?->start_time->format('H:i')}} - {{$day['info']?->end_time->format('H:i')}}</span>
                                            <div class="text-orange-500 ml-3">Ziek</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="cursor-pointer options hover:bg-white hover:shadow-md flex justify-center items-center"
                                @click="$dispatch('set-day-data', { 
                                    shiftID: '{{ $day['info']->id }}',
                                    'timeDiff': '{{ $hourDiff }}',
                                    'breakTime': '{{ $this->getBreakTime($hourDiff) }}'
                                }),
                                showModal = true"
                            >
                                <i class="text-[18px] bi bi-three-dots-vertical text-blue-500 p-2"></i>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    <section class="p-2">
        <livewire:shift-reply />
    </section>
    <section>
        <livewire:notifications.success />
    </section>
</div>
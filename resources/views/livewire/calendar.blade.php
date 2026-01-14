<?php

use function Livewire\Volt\{layout, title, state, mount, computed};
use App\Models\Workday;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

layout('components.layouts.master');
title('Werkrooster');

// Define state
state([
    'title' => 'Default Planner',
    'startsAt' => Carbon::now(),
    'selectedDate' => fn() => now()->toDateString(), // Track selection by YYYY-MM-DD
    'selectedId' => 1,
]);

mount(function () {

});

$dbConnection = computed(function () {
    try {
        DB::connection()->getPdo();
    } catch (\Exception $e) {
        // Database is down! Redirect or show a custom view
        return "error";
    }
});

$weekPeriod = computed(function () {
    $selectedDate = Carbon::parse($this->selectedDate);
    $startWeek =    $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
    $endWeek =      $selectedDate->copy()->endOfWeek(Carbon::SUNDAY);
    $startMonth =   $startWeek->translatedFormat('m');
    $endMonth =     $endWeek->translatedFormat('m');
    if($startMonth === $endMonth) $weekDisplay = $startWeek->translatedFormat('d').' t/m '.$endWeek->translatedFormat('d F');
    else $weekDisplay = $startWeek->translatedFormat('d M').' t/m '.$endWeek->translatedFormat('d M');
    
    return [
        'weekNum' => $selectedDate->weekOfYear,
        'weekDisplay' => $weekDisplay, 
    ];
});

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
            'dateStr' => $currentDay->translatedFormat('Y-m-d'),
            'isCurrentMonth' => $currentDay->month === $this->startsAt->month,
            'isToday' => $currentDay->isToday(),
        ];
        $currentDay->addDay();
    }

    return $days;
});

// Runs once in the mount and have the work data organized
$workdayRecords = computed(function (){

    $data = Workday::where('worker_id', 1)
        ->get()
        ->keyBy(fn($item) => $item->date->toDateString());

    return $data->map(function ($record){
        $hourDiff = $record->start_time?->diffInHours($record->end_time);
        $config = match($record->type) {
            'work'    => ['label' => 'shift',  'bg' => 'bg-blue-100',   'dot' => 'bg-blue-400', 'icon' => 'bi bi-clock'],
            'holiday' => ['label' => 'verlof', 'bg' => 'bg-orange-100', 'dot' => 'bg-yellow-500', 'icon' => 'bi bi-brightness-alt-high-fill'],
            'sick'    => ['label' => 'ziek',   'bg' => 'bg-red-200',    'dot' => 'bg-red-500', 'icon' => 'bi bi-info-circle'],
            default   => null
        };
        return collect([
            'workId' => $record->id,
            'config' => $config,
            'date' => $record->date->toDateString(),
            'workday' => $record->date->translatedFormat('l d M Y'),
            'worktime' => ($record->start_time) ? $record->start_time->translatedFormat('H:i') .' - '. $record->end_time->translatedFormat('H:i') : null,
            'hourDiff' => $hourDiff,
            'breakTime' => $record->getBreakTime($hourDiff),
        ]);
    });

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

        $weekdayRecords = $this->workdayRecords
            ->whereBetween('date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->toArray();

            
        // 3. Build the 7-day array for the UI
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $current = $startOfWeek->copy()->addDays($i);
            $dateStr = $current->toDateString();

            $days[] = [
                'date' => $current,
                'dateString' => $dateStr,
                'isToday' => $current->isToday(),
                'onlyPast' => $current->isPast() && !$current->isToday(),
                'isSelected' => $this->selectedDate === $dateStr,
                'details' => $weekdayRecords[$dateStr] ?? null,
            ];
        };
        return $days;

    } catch (\Exception $e) { //Database is down! 
        report($e); //Send error to logs 
        return [];
    }
});

?>

<div class="calendar px-8 py-10 sm:px-0" x-data="{ showModal: false, localLoading: false }"
        @open-modal.window="showModal = true" @close-modal.window="showModal = false">
    <div class="sm:w-[580px] md:w-[720px] mx-auto">

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

        <!-- Calender system section -->
        <section>

            <!-- Top section (month navigation) -->
            <div class="flex justify-center items-center mb-2">
                <button wire:click="prevDate"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-light px-2 rounded-full w-[35px] h-[35px] cursor-pointer">
                    <span><i class="bi bi-chevron-left"></i></span>
                </button>
                <h3 class="text-lg font-bold mx-3">
                    {{ $this->startsAt->translatedFormat('F Y') }}
                </h3>
                <button wire:click="nextDate"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-2 rounded-full w-[35px] h-[35px] cursor-pointer">
                    <span><i class="bi bi-chevron-right"></i></span>
                </button>
            </div>

            <!-- Head of calendar (Days of the week) -->
            <div class="text-[13px] bg-ah text-white grid grid-cols-7">
                <div class="p-2">
                    <p>ma</p>
                </div>
                <div class="p-2">
                    <p>di</p>
                </div>
                <div class="p-2">
                    <p>wo</p>
                </div>
                <div class="p-2">
                    <p>do</p>
                </div>
                <div class="p-2">
                    <p>vr</p>
                </div>
                <div class="p-2">
                    <p>za</p>
                </div>
                <div class="p-2">
                    <p>zo</p>
                </div>
            </div>

            <!-- Content of calendar (Days grid) -->
            <div wire:loading.class="opacity-50 grayscale" wire:target="selectDate()" class="gray-light grid grid-cols-7 mb-8 border border-gray-300">
                @foreach($this->calendarGrid as $day)

                @php
                    $info = $this->workdayRecords[$day['dateStr']] ?? null ;
                @endphp

                <!-- Dayblock (clickable button) -->
                <div wire:click="selectDate('{{ $day['dateStr'] }}')" class="p-2 cursor-pointer gap-1 h-[50px] transition duration-150 ease-in-out
                        {{ $this->selectedDate === $day['dateStr'] && !$day['isToday'] ? 'ring-2 ring-blue-400 ring-inset' : 'hover-gray' }}
                        {{ $day['isCurrentMonth'] ? '' : 'opacity-30' }}">
                    
                    <div class="sm:text-[13px] font-bold flex sm:justify-between items-center items-start">
                    @if($day['isToday'])
                        <!-- Today highlighter  -->
                        <p class="font-bold today flex justify-center items-center mt-[-2px] ml-[-4px] sm:ml-[-6px] sm:mt-[-4px]">{{ $day['date']->day }}</p>
                    @else
                        <!-- Daynumber of the month -->
                        <p class="font-bold ">{{ $day['date']->day }}</p>
                    @endif

                    <!-- Tags types for calendar categorization -->
                        @if( isset($info) )
                        <div>
                            <span class="text-[9px] px-1 rounded hidden sm:block {{ $info['config']['bg'] }}">
                                {{ $info['config']['label'] }}
                            </span>
                            <span class="dot sm:hidden ml-1 flex {{ $info['config']['dot'] }}"></span>
                        </div>
                        @endif
                    </div>

                    @if( isset($info) )
                        <div class="text-[10px] text-gray-600 hidden sm:block text-center">
                            {{ $info['worktime'] }}
                        </div>
                    @endif

                </div>
                @endforeach
            </div>

        </section>

        <!-- Week Information section-->
        <section>

            <!-- Head weekinfo -->
            <div class="text-center">
                <h4 class="text-lg font-bold">Mijn shiften in week {{ $this->weekPeriod['weekNum'] }}</h4>
                <p class="mb-2 sm:text-[14px] ">{{ $this->weekPeriod['weekDisplay'] }}</p>
            </div>

            <!-- Content weekinfo -->
            @foreach($this->selectedWeekDays as $day)
            @if( isset($day['details']) )
            <div class="sm:text-[14px] my-[6px] border border-gray-300 rounded-xl
                    {{$day['isSelected'] ? 'ring-1 ring-blue-400 ring-outset' : ''}}">
                <div class="flex justify-between items-center h-[65px] pl-3 rounded-xl {{$day['onlyPast'] ? 'gray-light' : 'bg-white'}}">

                    <div class="whitespace-nowrap overflow-x-hidden">
                        
                        <!-- Workday display-->
                        <span>{{$day['details']['workday']}}</span>
                        
                        <div class="flex gap-1">
                            <!-- Icons 1/4 Work type -->
                            <div class="flex-none w-[110px]">
                                <i class="text-blue-400 {{ $day['details']['config']['icon'] }}"></i>  
                                <span class="font-light">
                                    @if($day['details']['config']['label'] === 'shift')
                                    <span>{{ $day['details']['worktime'] }}</span>
                                    @elseif($day['details']['config']['label'] === 'verlof')
                                    <span>Vrij</span>
                                    @else
                                    <span>Ziek</span>
                                    @endif
                                </span>
                            </div>

                            @if($day['details']['config']['label'] === 'shift')
                            <!-- Icons 2/4 Total hours -->
                            <div class="flex-none w-[80px]">
                                <i class="bi bi-clock-history text-blue-400"></i>
                                <span class="font-light">
                                    {{ $day['details']['hourDiff'] }} u.
                                </span>
                            </div>

                            <!-- Icons 3/4 Break -->
                            <div class="flex-none w-[110px]">
                                <i class="bi bi-cup-hot text-blue-400"></i>
                                <span class="font-light">{{ $day['details']['breakTime'] }}</span>
                            </div>

                            <!-- Icons 4/4 Label -->
                            <div class="flex flew-no-wrap gap-1">
                                <i class="bi bi-tags text-blue-400"></i>
                                <span class="font-light">vullen</span>
                            </div>
                            @endif

                        </div>

                    </div>
                    
                    <!-- Dots button with dispatch -->
                    <div class="flex px-1 h-full items-center cursor-pointer bg-blue-400 hover:bg-blue-300 rounded-r-xl ransition duration-150 ease-in-out">
                        <div class="flex justify-center items-center" @click="$dispatch('set-day-data', { 
                                        shiftID: '{{ $day['details']['workId'] }}',
                                        'timeDiff': '{{ $day['details']['hourDiff'] }}',
                                        'breakTime': '{{ $day['details']['breakTime'] }}'
                                    }),
                                    showModal = true">
                            <i class="text-[18px] bi bi-three-dots-vertical text-white p-2"></i>
                        </div>
                    </div>

                </div>
            </div>
            @endif
            @endforeach
        </section>

        <!-- Modal for shift replies -->
        <section class="p-2">
            <livewire:shift-reply />
        </section>

    </div>
</div>
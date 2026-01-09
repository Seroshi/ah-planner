<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\Workday;
use Carbon\Carbon;

// Define state
state([
    'startsAt' => Carbon::now(),
    'selectedDate' => fn() => now()->toDateString(), // Track selection by YYYY-MM-DD
    'selectedId' => 1,
    'workday' => null,
]);

mount(function () {
    // return dd($this->getTest);
});

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

    //Grab the neccessary from clicked day and open in modal 
    if( isset($this->workdayData[$dateString]) ){

        $dataResult = $this->workdayData[$dateString];
        $hourDiff = round(Carbon::parse($dataResult['start_time'])->diffInHours(Carbon::parse($dataResult['end_time'])));
        $breakTime = Workday::getBreakTime($hourDiff);
        $this->dispatch('set-day-data', 
            shiftID: $dataResult['id'],
            timeDiff: $hourDiff,
            breakTime: $breakTime,
        );
        $this->dispatch('open-modal');

    }else{

        //Reset the modal to show no old data
        $this->dispatch('set-day-data', 
            shiftID: $this->selectedId,
            timeDiff: null,
            breakTime: null,
        );
    }

    

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
        $worker_id = 1;
        // Fetch all workdays and turn them into a key-value array [ 'date' => [data] ]
        return Workday::where('worker_id', $worker_id)->get()->keyBy(function ($item) {
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
    $data = $this->workdayData[$date->toDateString()] ?? null;
    $this->workday = $data;
    return $data;
};

?>

<div wire:poll.10s class="calendar sm:max-w-[650px] mx-auto" 
    x-data="{ showModal: false }" @open-modal.window="showModal = true" @close-modal.window="showModal = false"
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

    <!-- Calender system section -->
    <section>

        <!-- Top section (month navigation) -->
        <div class="flex justify-center items-center mb-2">
            <button wire:click="prevDate" 
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-light px-2 rounded-full w-[35px] h-[35px]"
            >
                <span><i class="bi bi-chevron-left"></i></span>
            </button>
            <h3 class="text-lg font-bold mx-3">
                {{ $this->startsAt->format('F Y') }}
            </h3>
            <button wire:click="nextDate" 
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-2 rounded-full w-[35px] h-[35px]"
            >
                <span><i class="bi bi-chevron-right"></i></span>
            </button>
        </div>

        <!-- Head of calendar (Days of the week) -->
        <div class="text-[13px] bg-ah text-white grid grid-cols-7">
            <div class="p-2"><p>ma</p></div>
            <div class="p-2"><p>di</p></div>
            <div class="p-2"><p>wo</p></div>
            <div class="p-2"><p>do</p></div>
            <div class="p-2"><p>vr</p></div>
            <div class="p-2"><p>za</p></div>
            <div class="p-2"><p>zo</p></div>
        </div>

        <!-- Content of calendar (Days grid) -->
        <div class="gray-light grid grid-cols-7 mb-6 border">
            @foreach($this->calendarGrid as $day) 
                @php 
                    $dateStr = $day['date']->toDateString();
                    $isSelected = $this->selectedDate === $dateStr;
                    $info = $this->getDayInfo($day['date']); 
                @endphp
                
                <!-- Dayblock (clickable button) -->
                <div wire:click="selectDate('{{ $dateStr }}')" class="p-2 cursor-pointer gap-1 h-[50px]
                    {{ $isSelected && !$day['isToday'] ? 'ring-2 ring-blue-400 ring-inset' : 'hover-gray' }}
                    {{ $day['isCurrentMonth'] ? '' : 'opacity-30' }}"
                >
                    <div class="sm:text-[13px] font-bold flex sm:justify-between items-center items-start">
                        @if($day['isToday'])
                            <!-- Today highlighter  -->
                            <p class="font-bold today flex justify-center items-center mt-[-2px] ml-[-4px] sm:ml-[-6px] sm:mt-[-4px]">
                                {{ $day['date']->day }}
                            </p>
                        @else
                            <!-- Daynumber of the month -->
                            <p class="font-bold ">{{ $day['date']->day }}</p>
                        @endif

                        <!-- Tags types for calendar categorization -->
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

    </section>

    <!-- Week Information section-->
    <section>

        <!-- Top section -->
        <div class="text-center">
            <h4 class="text-lg font-bold">Mijn shiften in week {{ $this->weekNumber }}</h4>
            <div class="mb-2 sm:text-[14px] ">
                @php
                    $dayData = $this->selectedWeekDays;
                    $firstDay = $dayData[0]['date'];
                    $lastDay = $dayData[6]['date'];
                @endphp
                
                @if($dayData)
                    @if($firstDay->format('m') == $lastDay->format('m'))
                        {{ $firstDay->format('d') }} t/m
                        {{ $lastDay->format('d') }}
                        <span>{{$firstDay->format('F')}}</span>
                    @else
                        <span>{{ $firstDay->format('d') }} {{ $firstDay->format('F') }} t/m 
                            {{ $lastDay->format('d') }} {{ $lastDay->format('F') }}</span>
                    @endif
                @endif
            </div>
        </div>

        <!-- Content section (week overview) -->
        @foreach($this->selectedWeekDays as $day)
            @php 
                $hourDiff = $day['info']?->start_time?->diffInHours($day['info']?->end_time) ?? 0;
                $getBreakTime = \App\Models\Workday::getBreakTime($hourDiff );
                $pastShift = $day['date']?->isPast() && !$day['isToday'];
            @endphp
            @if($day['info']?->type)
                <div class="sm:text-[14px] my-[6px] border rounded-xl
                    {{$day['isSelected'] ? 'ring-1 ring-blue-400 ring-outset' : ''}}"
                >
                    <div class="flex justify-between items-center h-[65px] rounded-xl
                        {{$pastShift ? 'gray-light' : 'bg-white'}}"
                    >
                        <div class="grow overflow-x-hidden px-3">
                            @if($day['info']?->type)

                                <!-- Shift day -->
                                <div class="whitespace-nowrap overflow-x-hidden">{{$day['date']->format('D d M Y')}}</div>
                                
                                <!-- Shift details -->
                                <div class="flex gap-1">
                                    @if($day['info']->type === 'work')

                                        <!-- Icons 1/4 Work type -->
                                        <div class="flex-none w-[110px]">
                                            @if($day['date']->isPast() && !$day['isToday'])
                                                <i class="bi bi-check-circle text-blue-400"></i>
                                            @else
                                                <i class="bi bi-clock text-blue-400"></i>
                                            @endif
                                            <span class="font-light">
                                                {{$day['info']?->start_time->format('H:i')}} - {{$day['info']?->end_time->format('H:i')}}
                                            </span>
                                        </div>

                                        <!-- Icons 2/4 Total hours -->
                                        <div class="flex-none w-[80px]">
                                            <i class="bi bi-clock-history text-blue-400"></i>
                                            <span class="font-light">
                                                {{ $hourDiff }} u.
                                            </span>
                                        </div>

                                        <!-- Icons 3/4 Break -->
                                        <div class="flex-none w-[110px]">
                                            <i class="bi bi-cup-hot text-blue-400"></i>
                                            <span class="font-light">{{ $getBreakTime }}</span>
                                        </div>

                                        <!-- Icons 4/4 Label -->
                                        <div class="flex flew-no-wrap gap-1">
                                            <i class="bi bi-tags text-blue-400"></i>
                                            <span class="font-light">vullen</span>
                                        </div>

                                    @elseif($day['info']->type === 'holiday')
                                        <!-- Icons 1/4 Holiday type -->
                                        <div class="flex-none w-[110px]">
                                            <i class="bi bi-brightness-alt-high-fill text-blue-400"></i>
                                            <span class="font-light">vrij</span>
                                        </div>

                                    @elseif($day['info']->type === 'sick')
                                        <!-- Icons 1/4 Sick type -->
                                        <div class="flex-none w-[110px]">
                                            <i class="bi bi-info-circle text-orange-500"></i>
                                            <span class="font-light">{{$day['info']?->start_time->format('H:i')}} - {{$day['info']?->end_time->format('H:i')}}</span>
                                            <div class="text-orange-500 ml-3">Ziek</div>
                                        </div>
                                    @endif

                                </div>
                            @endif
                        </div>

                        <!-- Dots button with dispatch -->
                        <div class="flex px-1 h-full items-center cursor-pointer bg-blue-400 hover:bg-blue-300 rounded-r-xl">
                            <div class="flex justify-center items-center"
                                @click="$dispatch('set-day-data', { 
                                    shiftID: '{{ $day['info']->id }}',
                                    'timeDiff': '{{ $hourDiff }}',
                                    'breakTime': '{{ $getBreakTime }}'
                                }),
                                showModal = true"
                            >
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
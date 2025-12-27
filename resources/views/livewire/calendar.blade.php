<?php

use function Livewire\Volt\{state, computed};
use Carbon\Carbon;

// Define state
state([
    'startsAt' => Carbon::now()->startOfMonth(),
]);

$nextMonth = function () {
    $this->startsAt = $this->startsAt->addMonth(); 
};

$prevMonth = function () {
    $this->startsAt = $this->startsAt->subMonth();
};

$calendarGrid = computed(function () {
    $days = [];
    $startOfGrid = $this->startsAt->copy()->startOfWeek(Carbon::MONDAY);
    $endOfGrid = $this->startsAt->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

    $currentDay = $startOfGrid->copy();

    while ($currentDay <= $endOfGrid) {
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
    $data = [];
    $today = Carbon::today();

    // 1. GENERATE FUTURE WORKDAYS (Next 2 Weeks)
    // Let's say you work every Monday, Wednesday, Friday
    for ($i = 0; $i <= 14; $i++) {
        $date = $today->copy()->addDays($i);
        
        // Example logic: Work on Mon(1), Wed(3), Fri(5)
        if (in_array($date->dayOfWeek, [1, 3, 5])) {
            $data[$date->toDateString()] = [
                'type' => 'Scheduled',
                'color' => 'bg-blue-100',
                'label' => 'Shift'
            ];
        }
    }

    // 2. ADD PAST REGISTERED DAYS (Hardcoded for now, Database later)
    // You can use specific dates as keys
    $pastEntries = [
        $today->copy()->subDays(5)->toDateString() => ['type' => 'work', 'label' => 'Completed'],
        $today->copy()->subDays(2)->toDateString() => ['type' => 'sick', 'label' => 'Sick Leave'],
    ];

    // Merge them together
    return array_merge($data, $pastEntries);
});

// Helper function to check the date string
$getDayInfo = function ($date) {
    return $this->workdayData[$date->toDateString()] ?? null;
};

?>

<div class="calendar">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-[#00A2E5]">
            {{ $this->startsAt->format('F Y') }}
            <div class="space-x-2">
                <button wire:click="prevMonth" ...>Prev</button>
                <button wire:click="nextMonth" ...>Next</button>
            </div>
        </h2>
    </div>

    <div class="inline-flex mb-4">
        <button class="bg-gray-200 hover:bg-blue-200 text-gray-400 hover:text-gray-500 font-bold py-2 px-4 rounded-l">
            week
        </button>
        <button class="bg-blue-400 text-gray-800 font-bold text-white py-2 px-4 rounded-r">
            maand
        </button>
    </div>

    <div class="bg-[#EEEEEE] grid grid-cols-7">
        <div class="p-2"><p>ma</p></div>
        <div class="p-2"><p>di</p></div>
        <div class="p-2"><p>wo</p></div>
        <div class="p-2"><p>do</p></div>
        <div class="p-2"><p>vr</p></div>
        <div class="p-2"><p>za</p></div>
        <div class="p-2"><p>zo</p></div>
    </div>
    <div class="bg-[#EEEEEE] font-bold grid grid-cols-7">
        @foreach($this->calendarGrid as $day) 
            <div class="p-2 day">
                <div class="flex justify-between items-start">
                    @if($day['isToday'])
                        <p class="font-bold today">
                            {{ $day['date']->day }}
                        </p>
                    @elseif($day['isCurrentMonth'])
                        <p class="font-bold">
                            {{ $day['date']->day }}
                        </p>
                    @else
                        <p class="font-bold text-gray-400">
                            {{ $day['date']->day }}
                        </p>
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
                    <div class="mt-2 text-[10px] text-gray-600">
                        {{ $info['label'] }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
<?php

use function Livewire\Volt\{layout, title, state, computed, uses};
use Livewire\WithPagination;
use App\Models\Workday;
use Carbon\Carbon;

layout('components.layouts.master');
title('Shift ruil');

state([
    'user_id' => 1,
    'swapAllowed' => false,
]);

$getDetails = computed(function(){

    $workdays = $this->getWorkdays; 

    return $workdays->through(function ($shift) {
        $hourDiff = $shift->start_time?->diffInHours($shift->end_time);

        return [
            'workId'    => $shift->id,
            'workday'   => $shift->date->translatedFormat('l d M Y'),
            'worktime'  => $shift->start_time?->translatedFormat('H:i') . ' - ' . $shift->end_time?->translatedFormat('H:i'),
            'hourDiff'  => $hourDiff,
            'breakTime' => $shift->getBreakTime($hourDiff),
        ];
    });
});

$getWorkdays = computed(function(){
    return Workday::where('worker_id', $this->user_id)
        ->whereNotNull('start_time')
        ->whereDate('date', '>=', now()->addDays(2))
        ->orderBy('created_at', 'asc')
        ->paginate(8);
});

?>

<div class="px-8 py-10 sm:px-0">

    <!-- Week Information section-->
    <section class="sm:w-[580px] md:w-[720px] mx-auto">

        <!-- Top section -->
        <div class="text-left mb-4 w-[350px] mx-auto" id="test">
            <h4 class="text-center text-lg font-bold">Mijn ruilbare shiften</h4>
            <div>
                <i class="ml-[50px] bi bi-check-circle-fill text-blue-400"></i>
                <span class="text-sm text-gray-400">Minimaal 2 dagen van te voren.</span>
            </div>
            <div>
                <i class="ml-[50px] bi bi-check-circle-fill text-blue-400"></i>
                <span class="text-sm text-gray-400">Pas definitief na akkoord van je manager.</span>
            </div>
        </div>


        <!-- Content section (week overview) -->
        @foreach($this->getDetails as $shift)
        <div class="sm:text-[14px] my-[6px] border border-gray-300 rounded-xl">
            <a href="{{route('shift.swap', $shift['workId'])}}" navigate: true @click="showModal = true"
                    class="flex justify-between items-center h-[65px] rounded-xl bg-white hover:bg-gray-100 transition delay-50 group cursor-pointer">
                <div class="grow overflow-x-hidden px-3">

                    <!-- Shift day -->
                    <div class="whitespace-nowrap overflow-x-hidden">{{ $shift['workday'] }}</div>

                    <!-- Shift details -->
                    <div class="flex gap-1">
                        
                        <!-- Icons 1/4 Work type -->
                        <div class="flex-none w-[110px]">
                            <i class="bi bi-clock text-blue-400"></i>
                            <span class="font-light">{{ $shift['worktime'] }}</span>
                        </div>

                        <!-- Icons 2/4 Total hours -->
                        <div class="flex-none w-[80px]">
                            <i class="bi bi-clock-history text-blue-400"></i>
                            <span class="font-light">
                                {{ $shift['hourDiff'] }} u.
                            </span>
                        </div>

                        <!-- Icons 3/4 Break -->
                        <div class="flex-none w-[110px]">
                            <i class="bi bi-cup-hot text-blue-400"></i>
                            <span class="font-light">
                                {{ $shift['breakTime'] }}
                            </span>
                        </div>

                        <!-- Icons 4/4 Label -->
                        <div class="flex flew-no-wrap gap-1">
                            <i class="bi bi-tags text-blue-400"></i>
                            <span class="font-light">vullen</span>
                        </div>

                    </div>
                </div>

                <div>
                    <div class="btn bg-blue-400 group-hover:bg-blue-500 text-white flex whitespace-nowrap py-2 px-4 mt-4 mb-4 mr-3 transition delay-50">
                        <span class="mr-1">Shift ruilen</span>
                        <span><i class="bi bi-arrow-repeat inline-block transition-transform duration-500 group-hover:rotate-180"></i></span>
                    </div>
                </div>

            </a>
        </div>
        @endforeach

        <div class="mt-4">{{ $this->getDetails->links() }}</div>

    </section>

</div>

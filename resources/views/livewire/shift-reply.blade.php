<?php

use function Livewire\Volt\{state, mount, rules, computed, on};
use App\Models\Workday;
use App\Models\Message;
use Carbon\Carbon;

//Form fields
state([
    'workdayId' => null, // Store ONLY the ID in state
    'display' => [],
    'condition' => [],
    'topic' => '',
    'remark' => '',
    'topicOptions' => Workday::getTopicOptions(),
]);

$workday = computed(function () {
    return $this->workdayId ? Workday::find($this->workdayId) : null;
});

//Form validations
rules([
    'topic' => 'required',
    'remark' => 'required|string|max:500',
]);

//Update state values for this modal upon click (from calendar component)
on(['set-day-data' => function ($shiftID, $timeDiff, $breakTime) {
    $this->workdayId = $shiftID;
    $workday = Workday::find($shiftID);
    $this->workday = $workday;

    $types = [ //Changing the type names to Dutch
        'work'    => 'shift',
        'holiday' => 'verlof',
        'sick' => 'shift (ziek gemeld)',
    ];

    //Values received from the 'calendar' volt component 
    $this->display = [
        'date'      => $workday->date->format('l d M Y'),
        'type'      => $types[$workday->type] ?? 'unknown',
        'startTime' => $workday->start_time?->format('H:i') ?? '',
        'endTime'   => $workday->end_time?->format('H:i') ?? '',
        'timeDiff'  => $timeDiff,
        'breakTime' => $breakTime,
        'swapAllowedInTime' => Workday::swapAllowedInTime($workday->date),
    ];

    $this->condition = [
        'isHoliday' => ($workday->type === 'holiday') ? true : false,
    ];

    //Form fields from this component (found below)
    $this->topic = $workday->topic ?? '';
    $this->remark = $workday->remark ?? '';
}]);

$save = function () {
    $this->validate();

    // return dd($data);
    $getWorker = \App\Models\Worker::firstOrFail();

    $message = Message::create([
        'worker_id' => $getWorker?->id,
        'workday_id' => $this->workday->id ?? null,
        'topic' => $this->topic,
        'remark' => $this->remark,
        'status' => 0,
        'read' => 0,
    ]);

    // Clear the form
    $this->reset(['topic', 'remark']);

    // Dispatch a success notification
    session()->flash('notification', 'Opmerking verstuurd!');

    return $this->redirectRoute('home', navigate: true);
};

?>

<div class="flex justify-center items-center z-50" style="position:fixed; width:100vw; height:100vh; top:0; left:0; background:rgba(0,0,0,0.5);"
    x-show="showModal" x-cloak
>
    <div class="bg-white p-6 mr-3 rounded-md shadow-md w-[90%] max-w-lg relative" @click.away="showModal = false">
        
        <div class="absolute top-[-15px] right-[-15px] text-xs text-white w-8 h-8 bg-gray-600 hover:bg-gray-800 rounded-full flex justify-center items-center 
            cursor-pointer shadow-md duration-200"
            @click="showModal = false"
        >
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="space-y-4">
    
            @if($this->workday)
            <div>
                <!-- Sub info -->
                <div class="text-gray-500">
                    <div><i class="bi bi-building text-gray-400""></i> AH: 1645</div>
                    <div><i class="bi bi-check-circle text-gray-400"></i> Geregistreerd door: Jeroen Blankeveld</div>
                </div>

                <h3 class="text-xl font-bold b-ah-border pb-1 mb-3 my-4">Mijn shift</h3>

                <!-- Workday -->
                <div>
                    <i class="bi bi-calendar4-week text-blue-400 mr-1 mb-2"></i>
                    <span>{{ $display['date'] }}</span>
                </div>

                <div class="flex gap-x-1 flex-wrap ">
                    <!-- Shift time -->
                    <div class="w-[140px]">
                        @if($condition['isHoliday'])
                            <i class="bi bi-brightness-alt-high-fill text-blue-400 mr-1"></i>
                            <span>Vrij</span>
                        @else
                            <i class="bi bi-clock text-blue-400 mr-1"></i>
                            <span>{{ $display['startTime'] }}</span>
                        @endif
                    </div>

                    <!-- Label -->
                    <div>
                        <i class="bi bi-tags text-blue-400 mr-1"></i>
                        <span>Vullen</span>
                    </div>
                </div>

                <div class="flex gap-x-1 flex-wrap">
                    <!-- Total hours -->
                    <div class="w-[140px]">
                        <i class="bi bi-clock-history text-blue-400 mr-1"></i>
                        <span>{{ $display['timeDiff'] }} uren</span>
                    </div>

                    <!-- Breaktime -->
                    <div>
                        <i class="bi bi-cup-hot text-blue-400 mr-1"></i>
                        <span>{{ $display['breakTime'] }}</span>
                    </div>
                </div>

                <!-- Only show if 7 days in the future -->
                @if($display['swapAllowedInTime'] && $this->workday->type != 'holiday')
                    <a href="{{route('shift.swap', $this->workdayId)}}" class="btn bg-ah bg-ah-hover text-white py-2 px-10 group mt-4 mb-4">
                        <span class="mr-1">Shift ruilen</span>
                        <span><i class="bi bi-arrow-repeat inline-block transition-transform duration-500 group-hover:rotate-180"></i></span>
                    </a>
                @endif
        
            </div>
            @endif

            @if(!$this->remark)
                <div class="mt-6">
                    <h3 class="b-border pb-2">Klopt er iets niet? Laat het hieronder weten!</h3>
                </div>
            @endif

            <form wire:submit.prevent="save">
                <div>
                    <label for="topic" class="block text-base font-bold mb-1">Onderwerp *</label>
                    <select wire:model="topic" class="bg-gray-100 rounded-md" @disabled($this->topic)>
                        <option value="" disabled>Kies een onderwerp</option>
                        @foreach($this->topicOptions as $index => $option)
                            <!-- Skip shift ruil -->
                            @if($index != 4)
                                <option value="{{$index}}">{{$option['value']}}</option>
                            @endif
                        @endforeach
                    </select>
                    <div>@error('topic') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror</div>
                </div>

                <div class="mt-2">
                    <label for="remark" class="text-base font-bold">Bericht *</label>
                    <textarea wire:model="remark" @readonly($this->remark) class="w-full bg-gray-100 rounded-md focus:border-blue focus:outline-none focus:ring-1 mt-2 p-2"></textarea>
                    @error('remark') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                @if(!$this->remark)
                    <button type="submit" class="w-full text-white mt-2 py-2 rounded-xl bg-ah bg-ah-hover">
                        Stuur opmerking
                    </button>
                @else
                    <div class="w-full flex justify-center bg-gray-200 text-gray-800 py-2 rounded-md">
                        Jouw reactie is in behandeling <span><i class="bi bi-clock-history ml-2"></i></span>
                    </div>
                @endif
            </form>
        </div>
    <div>

</div>
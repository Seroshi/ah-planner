<?php

use function Livewire\Volt\{state, mount, rules, computed, on};
use App\Models\Workday;
use App\Models\Message;
use Carbon\Carbon;

//Form fields
state([
    'workdayId' => null, // Store ONLY the ID in state
    'display' => [],
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
    ];

    //Form fields from this component (found below)
    $this->topic = $workday->topic ?? '';
    $this->remark = $workday->remark ?? '';
}]);

$save = function () {
    $this->validate();

    // Update existing remark in database if found
    // $this->workday?->update([
    //     'topic' => $this->topic,
    //     'remark' => $this->remark,
    // ]);

    // $data = collect([
    //     'workday_id' => $this->workday->id,
    //     'topic' => $this->topic,
    //     'remark' => $this->remark,
    //     'status' => 0,
    //     'read' => 0,
    // ]);

    // return dd($data);
    $getWorker = \App\Models\Worker::firstOrFail();

    $message = Message::create([
        'worker_id' => $getWorker?->id,
        'workday_id' => $this->workday->id,
        'topic' => $this->topic,
        'remark' => $this->remark,
        'status' => 0,
        'read' => 0,
    ]);

    //Refresh messages volt component 
    $this->dispatch('refresh-data'); 

    // Dispatch a browser event
    $this->dispatch('close-modal');

    // Clear the form
    $this->reset(['topic', 'remark']);

    // Dispatch a success notification
    $this->dispatch('success', message: 'Opmerking verstuurd!');
};

?>

<div class="flex justify-center items-center z-50" style="position:fixed; width:100vw; height:100vh; top:0; left:0; background:rgba(0,0,0,0.5);"
    x-show="showModal" x-cloak
>
    <form wire:submit.prevent="save" class="bg-white p-6 mr-3 rounded-md shadow-md w-[90%] max-w-lg relative" @click.away="showModal = false">
        
        <div class="absolute top-[-15px] right-[-15px] text-xs text-white w-8 h-8 bg-gray-600 hover:bg-gray-800 rounded-full flex justify-center items-center 
            cursor-pointer shadow-md duration-200"
            @click="showModal = false"
        >
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="space-y-4">
    
            @if($this->workday)
            <div>
                <div class="text-gray-500">
                    <div><span><i class="bi bi-building text-gray-400""></i></span> AH: 1645</div>
                    <div><span><i class="bi bi-check-circle text-gray-400"></i></span> Geregistreerd door: Jeroen Blankeveld</div>
                </div>
                
                <h3 class="text-xl font-bold mt-4">Jouw {{ $display['type'] }}</h3>
                <div class="flex gap-10">
                    <span>{{ $display['date'] }}</span>
                </div>

                <div class="flex gap-6">
                    <span><i class="bi bi-clock text-blue-400"></i> {{ $display['startTime'] }} - {{ $display['endTime'] }}</span>
                    <span><i class="bi bi-tags text-blue-400"></i> Vullen</span>
                </div>
                <div class="flex gap-6">
                    <span><i class="bi bi-clock-history text-blue-400"></i> {{ $display['timeDiff'] }} u.</span> 
                    <span><i class="bi bi-cup-hot text-blue-400"></i> {{ $display['breakTime'] }}</span>
                </div>
            </div>
            @endif

            @if(!$this->remark)
                <div class="mt-6">
                    <h3 class="b-border pb-2">Klopt er iets niet? Laat het hieronder weten!</h3>
                </div>
            @endif

            <div>
                <label for="topic" class="block text-base font-bold mb-1">Onderwerp *</label>
                <select wire:model="topic" @disabled($this->topic) class="bg-gray-100 rounded-md p-2">
                    <option value="" disabled>Kies een onderwerp</option>
                    @foreach($this->topicOptions as $index => $option)
                    <option value="{{$index}}">{{$option['value']}}</option>
                    @endforeach
                </select>
                <div>@error('topic') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror</div>
            </div>

            <div>
                <label for="remark" class="text-base font-bold">Bericht *</label>
                <textarea wire:model="remark" @readonly($this->remark) class="w-full bg-gray-100 rounded-md focus:border-blue focus:outline-none focus:ring-1 mt-2 p-2"></textarea>
                @error('remark') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            @if(!$this->remark)
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                    Stuur opmerking
                </button>
            @else
                <div class="w-full flex justify-center bg-gray-200 text-gray-800 py-2 rounded-md">
                    Jouw reactie is in behandeling <span><i class="bi bi-clock-history ml-2"></i></span>
                </div>
            @endif
        </div>
    </form>

</div>

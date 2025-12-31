<?php

use function Livewire\Volt\{state, rules, on};
use App\Models\Workday;
use Carbon\Carbon;

//Form fields
state([
    'shiftID' => '',
    'date' => '',
    'type' => '',
    'startTime' => '',
    'endTime' => '',
    'timeDiff' => '',
    'breakTime' => '',
    'remark' => '',
]);

//Form validations
rules([
    'remark' => 'required|string|max:500',
]);

on(['set-day-data' => function ($shiftID, $timeDiff, $breakTime) {
    $workday = Workday::find($shiftID);
    if($workday->type === 'work') $this->type = 'shift';
    elseif($workday->type === 'holiday') $this->type = 'verlof';
    $this->shiftID = $shiftID;
    $this->date = $workday->date->format('l d M Y');
    $this->timeDiff = $timeDiff;
    $this->breakTime = $breakTime;
    $this->startTime = $workday->start_time->format('H:i');
    $this->endTime = $workday->end_time->format('H:i');
    $this->remark = old('remark', $workday->remark ?? '');
}]);

$save = function () {
    $this->validate();

    // Find shift by ID in databaes
    $workday = Workday::find($this->shiftID);

    if($workday){
        // Update existing remark in database
            $workday->update([
            'remark' => $this->remark,
        ]);
    }

    // Clear the form
    $this->reset();

    // Dispatch a success notification
    $this->dispatch('success', message: 'Opmerking verstuurd!');
};

?>

<div class="flex justify-center items-center z-50" style="position:fixed; width:100vw; height:100vh; top:0; left:0; background:rgba(0,0,0,0.5);">
    <form wire:submit.prevent="save" class="bg-white p-6 mr-3 rounded-md shadow-md w-[90%] max-w-lg relative" @click.away="showModal = false">
        
        <div class="absolute top-[-15px] right-[-15px] text-xs text-white w-8 h-8 bg-gray-600 hover:bg-gray-800 rounded-full flex justify-center items-center 
            cursor-pointer shadow-md duration-200"
            @click="showModal = false"
        >
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="space-y-4">
    
            <div>
                <div class="text-gray-500">
                    <div><span><i class="bi bi-building text-blue-400""></i></span> AH: 1645</div>
                    <div><span><i class="bi bi-check-circle text-blue-400"></i></span> Goedgekeurd door: Jeroen Blankeveld</div>
                </div>
                <h3 class="text-blue-400 font-bold mt-2">Jouw {{$this->type}}:</h3>
                <div class="flex gap-10">
                    <span>{{ $this->date }}</span>
                </div>
                <div class="flex gap-6">
                    <span><i class="bi bi-clock text-blue-400"></i> {{ $this->startTime }} - {{ $this->endTime }}</span>
                    <span><i class="bi bi-tags text-blue-400"></i> Vullen</span>
                </div>
                <div class="flex gap-6">
                    <span><i class="bi bi-clock-history text-blue-400"></i> {{ $this->timeDiff }} u.</span> 
                    <span><i class="bi bi-cup-hot text-blue-400"></i> {{ $this->breakTime }}</span>
                </div>
            </div>

            <div>
                <label class="block text-m font-bold text-blue-400">Klopt er iets niet? Laat het hier weten!</label>
                <textarea wire:model="remark" class="w-full bg-gray-100 rounded-md focus:border-blue focus:outline-none focus:ring-1 my-2 p-2"></textarea>
                @error('remark') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                Stuur opmerking
            </button>
        </div>
    </form>

</div>

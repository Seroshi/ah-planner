<?php

use function Livewire\Volt\{layout, title, state, mount, computed};
use App\Models\Workday;
use App\Models\Worker;
use App\Models\Message;
use App\Models\ShiftSwap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

layout('components.layouts.master');
title('Shift ruil');

state([
    'user_id' => 1,
    'workday' => '',
    'display' => [],
    'condition' => [],
    'keywords' => '',
    'searchResults' => [],
    'selectedWorkerId' => '',
    'noResults' => false,
]);

mount(function (Workday $workday) {
    $this->workday = $workday;
    $hourDiff = $workday->start_time->diffInHours($workday->end_time);
    $this->display = [
        'label' => $workday->label,
        'worker' => $workday->worker->full_name,
        'workday' => $workday->date->translatedFormat('l d M Y'),
        'worktime' => $workday->start_time->translatedFormat('H:i')." - ".$workday->end_time->translatedFormat('H:i'),
        'hourDiff' => $hourDiff,
        'breaktime' => $workday->getBreakTime($hourDiff),
    ];
    $this->condition = [
        'isHoliday' => ($workday->type === 'holiday') ? true : false,
    ];
    
    $this->searchResults = $this->collectionData();
});

//Confirms your worker choice and limit list to that worker
$setWorkerId = function($id){
    $this->selectedWorkerId = $id;
    $this->searchResults = Worker::where('id', $id)->get();
};

// Find worker based on ID 
$getWorker = computed(function(){
    return Worker::findOrFail($this->selectedWorkerId);
});

// Get the full list of workers
$collectionData = computed(function() {
    return Worker::orderBy('first_name', 'asc')
        ->where('id', '!=', $this->user_id)
        ->get();
});

// Show results of the keywords search
$searchWorkers = function() {

    $data = Worker::where('first_name', 'like', '%'.$this->keywords.'%')
        ->orWhere('middle_part', 'like', '%'.$this->keywords.'%')
        ->orWhere('last_name', 'like', '%'.$this->keywords.'%')
        ->where('id', '!=', $this->user_id)
        ->limit(10)
        ->get();

    if(count($data) >= 1){
        $this->searchResults = $data;
        $this->noResults = false;
    }else{
        $this->searchResults = '';
        $this->noResults = true;
    }
    
};

// Reset values from the keywords and the selected worker
$resetList = function(){
    $this->selectedWorkerId = '';
    if(!empty($this->keywords)) $this->keywords = '';
    $this->noResults = false;
    $this->searchResults = $this->collectionData();
};

//Creates the shift swap request
$saveData = function(){

    // Create and associate the two records together, 'record'_id fields will auto inject 
    DB::transaction(function () {

        $message = Message::create([
            'worker_id' => $this->user_id,
            'receiver_id' => $this->selectedWorkerId,
            'workday_id' => $this->workday->id,
            'topic' => 4, // 'Shift ruil verzoek'
            'remark' => 'Hoi, kan je deze dienst van me overnemen?',
            'replier' => $this->getWorker?->full_name,
            'status' => false,
        ]);

        $shiftSwap = ShiftSwap::create([
            'requester_id' => $this->user_id,
            'receiver_id' => $this->selectedWorkerId,
            'workday_id_1' => $this->workday->id,
            'status' => false,
        ]);

        $shiftSwap->message()->associate($message);

        $shiftSwap->save();
    });

    // Dispatch a success notification
    session()->flash('notification', 'Shiftruil verzoek verstuurd!');

    return $this->redirectRoute('messages', navigate: true);

};

?>


<div class="px-8 py-10 sm:px-0" x-data="{ showModal: false }" @close-modal.window="showModal = false">

    <section class="sm:w-[580px] md:w-[720px] mx-auto">

        <h3 class="text-xl font-bold b-ah-border pb-1 mb-3">Mijn shift</h3>

        <!-- Workday -->
        <div>
            <i class="bi bi-calendar4-week text-blue-400 mr-1 mb-2"></i>
            <span>{{ $display['workday'] }}</span>
        </div>

        <div class="flex gap-x-1 flex-wrap ">
            <!-- Shift time -->
            <div class="w-[140px]">
                @if($condition['isHoliday'])
                    <i class="bi bi-brightness-alt-high-fill text-blue-400 mr-1"></i>
                    <span>Vrij</span>
                @else
                    <i class="bi bi-clock text-blue-400 mr-1"></i>
                    <span>{{ $display['worktime'] }}</span>
                @endif
            </div>

            <!-- Label -->
            <div>
                <i class="bi bi-tags text-blue-400 mr-1"></i>
                <span>vullen</span>
            </div>
        </div>

        <div class="flex gap-x-1 flex-wrap">
            <!-- Total hours -->
            <div class="w-[140px]">
                <i class="bi bi-clock-history text-blue-400 mr-1"></i>
                <span>{{ $display['hourDiff'] }} uren</span>
            </div>

            <!-- Breaktime -->
            <div>
                <i class="bi bi-cup-hot text-blue-400 mr-1"></i>
                <span>{{ $display['breaktime'] }}</span>
            </div>
        </div>

        <h3 class="text-xl font-bold b-border pb-1 mb-3 mt-6">
            <div class="flex flex-wrap gap-2 items-center">
                <span>Ruilen met</span>
                @if($this->selectedWorkerId > 0)
                    <span class="color-ah mr-2">{{ $this->getWorker?->full_name }}?</span>
                    <button wire:click="saveData()" @click="showModal = true" class="btn bg-ah bg-ah-hover text-white py-1 px-5">
                        Ja <i class="bi bi-chevron-right ml-1 inline-block animate-bounce-x"></i>
                    </button>
                    <div wire:click="resetList()" class="btn bg-gray-100 hover:bg-gray-300 rounded-full w-[35px] h-[35px]">
                        <span class="flex items-center justify-center">
                            <i class="bi bi-x font-xl mt-[3px]"></i>
                        </span>
                    </div>
                @else
                    <span>
                        wie? 
                        <i class="ml-1 bi bi-arrow-repeat"></i>
                    </span>
                @endif
            </div>
        </h3>
        
        <!-- Search form -->
        <form wire:submit.prevent="searchWorkers" wire:key="sub-form" class="transition delay-150">

            <div class="flex gap-2 mb-3">
                <div class="grow relative">
                    <label for=""></label>
                    <input wire:model="keywords" class="py-2 px-4 bg-gray-100 w-full rounded-xl focus:outline focus:outline-blue-600" type="text" placeholder="Vind collega op naam">
                    <div wire:click="resetList()" class="absolute right-3 top-[50%] translate-y-[-50%]">
                        <i class="bi bi-x-circle-fill text-gray-300 hover:text-gray-500 cursor-pointer text-xl"></i>
                    </div>
                </div>
                <div class="w-[100px]">
                    <button type="submit" class="w-full bg-ah bg-ah-hover text-white py-2 btn">
                        Zoek
                    </button>
                </div>
            </div>

            @if($this->noResults) <div class="p-3 bg-gray-200 rounded-lg mt-2 mb-4">Geen resultaten gevonden.</div> @endif
            
            <!-- List of results / workers -->
            <div class="border border-gray-300 rounded-xl overflow-hidden ">
                @if($this->searchResults)
                    @foreach($this->searchResults as $person)
                        <div wire:click="setWorkerId({{$person->id}})" class="w-full border-b last:border-b-0 border-gray-300 py-1 px-3 hover:bg-gray-100 cursor-pointer">
                            {{ $person->first_name }}
                            {{ $person->middle_part }} 
                            {{ $person->last_name }}
                        </div>
                    @endforeach
                @endif
            </div>
            
        </form>

    </section>

</div>

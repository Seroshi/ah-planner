<?php

use function Livewire\Volt\{state, mount, computed};
use App\Models\Workday;
use App\Models\Worker;
use App\Models\Message;
use App\Models\ShiftSwap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


state([
    'workday' => '',
    'display' => [],
    'condition' => [],
    'keywords' => '',
    'searchResults' => [],
    'selectedWorkerId' => '',
]);

mount(function (Workday $workday) {
    $this->workday = $workday;
    $hourDiff = $workday->start_time->diffInHours($workday->end_time);
    $this->display = [
        'label' => $workday->label,
        'worker' => $workday->worker->full_name,
        'workday' => $workday->date->format('l d M Y'),
        'worktime' => $workday->start_time->format('H:i')." - ".$workday->end_time->format('H:i'),
        'hourDiff' => $hourDiff,
        'breaktime' => $workday->getBreakTime($hourDiff),
    ];
    $this->condition = [
        'isHoliday' => ($workday->type === 'holiday') ? true : false,
    ];
});

//Grab the worker ID and assign to getWorkerId
$setWorkerId = function($id){
    $this->selectedWorkerId = $id;
};

$getWorker = computed(function(){
    return Worker::findOrFail($this->selectedWorkerId);
});

//Either show search resuls or the full list
$collectionData = computed(function() {
    // 1 Return a collection of just the selected worker
    if ($this->selectedWorkerId > 0) {
        return Worker::where('id', $this->selectedWorkerId)->get();
    }

    // 2. Return only the search results
    if (!empty($this->keywords) && count($this->searchResults) > 0) {
        return $this->searchResults;
    }

    // 3. Default: Return the full list
    return Worker::orderBy('first_name', 'asc')->get();
});

// Subform to select a worker
$choose = function () {

    $data = Worker::where('first_name', 'like', '%'.$this->keywords.'%')
        ->orWhere('middle_part', 'like', '%'.$this->keywords.'%')
        ->orWhere('last_name', 'like', '%'.$this->keywords.'%')
        ->limit(10)
        ->get();

    if(count($data) == 1) $this->selectedWorkerId = $data->first()->id;
    else $this->selectedWorkerId = ''; // Reset selected worker

    $this->searchResults = $data;

    // Clear the form
    // $this->reset(['keywords']);
};

//Creates the shift swap request
$saveData = function(){

    // Create and associate the two records together, 'record'_id fields will auto inject 
    DB::transaction(function () {

        $message = Message::create([
            'worker_id' => 1,
            'receiver_id' => $this->selectedWorkerId,
            'workday_id' => $this->workday->id,
            'topic' => 4, // 'Shift ruil verzoek'
            'remark' => 'Hoi, kan je deze dienst van me overnemen?',
            'replier' => $this->getWorker?->full_name,
            'status' => false,
        ]);

        $shiftSwap = ShiftSwap::create([
            'requester_id' => 1,
            'receiver_id' => $this->selectedWorkerId,
            'workday_id_1' => $this->workday->id,
            'status' => false,
        ]);

        $shiftSwap->message()->associate($message);

        $shiftSwap->save();
    });

    // Dispatch a success notification
    session()->flash('notification', 'Shiftruil verzoek verstuurd!');

    return $this->redirectRoute('home', navigate: true);

};

$resetList = function(){
    $this->selectedWorkerId = '';
};

?>


<div class="w-full px-5" x-data="{ showModal: false }" @close-modal.window="showModal = false">

    <section class="w-full sm:w-[600px] max-w-4xl px-6 py-6 mx-auto mb-10 bg-white p-3 border rounded-xl">

        <!-- Breadcrumbs -->
        <div class="flex items-center mb-8">
            <a href="{{route('home')}}">Werkrooster</a>
            <i class="bi bi-chevron-right text-xs mx-2 stroke-1"></i>
            <span class="text-gray-400">Shift ruilen</span>
        </div>

        <div class="text-gray-500 mb-4">
            <div><i class="bi bi-building text-gray-400""></i> AH: 1645</div>
            <div><i class="bi bi-check-circle text-gray-400"></i> Geregistreerd door: Jeroen Blankeveld</div>
        </div>

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
                <span>Vullen</span>
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
                    <span class="color-ah">{{ $this->getWorker?->first_name }}?</span>
                    <button wire:click="saveData()" @click="showModal = true" class="btn bg-ah bg-ah-hover text-white py-1 px-5">
                        Ja <i class="bi bi-chevron-right ml-1 inline-block transition-transform duration-500 animate-bounce-x"></i>
                    </button>
                    <div wire:click="resetList()" class="btn bg-gray-100 hover:bg-gray-300 rounded-full w-[35px] h-[35px] flex items-center justify-center">
                        <i class="bi bi-x font-xl"></i>
                    </div>
                @else
                    <span>
                        wie? 
                        <i class="ml-1 bi bi-arrow-repeat"></i>
                    </span>
                @endif
            </div>
        </h3>
        
        <form wire:submit.prevent="choose" wire:key="sub-form">
            <div class="flex gap-2 mb-3">
                <div class="grow">
                    <label for=""></label>
                    <input wire:model="keywords" class="py-2 px-4 bg-gray-100 w-full rounded-xl" type="text" placeholder="Vind op naam">
                </div>
                <div class="w-[80px]">
                    <button type="submit" class="w-full bg-ah bg-ah-hover text-white py-2 rounded-full">
                        Zoek
                    </button>
                </div>
            </div>
            
            <div class="">
                @if($this->collectionData)
                    @foreach($this->collectionData as $person)
                        <div wire:click="setWorkerId({{$person->id}})" class="w-full border py-1 px-3 hover:bg-gray-100 cursor-pointer">
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

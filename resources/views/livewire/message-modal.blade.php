<?php

use function Livewire\Volt\{state, on, computed};
use App\Models\Message;

state([
    'message' => null,
    'display' => [
        'workdate' => '',
        'worktime' => '',
        'workername' => '',
        'topic' => '',
        'remark' => '',
        'response' => '',
    ],
]);

$message = computed(function () {
    return $this->message ? Message::find($this->message) : null;
});

// Received from messages dispatch function
on(['set-modal-message-data' => function($messageId, $topic){
    $msg = Message::findOrFail($messageId);
    $this->message = $msg;

    if($msg->workday?->type != 'holiday'){
        $worktime = $msg->workday?->start_time->format('H:i').' - '.$msg->workday?->end_time->format('H:i');
    }
    else{
        $worktime = 'vrij';
    }

    // Update the status of each message to read
    $msg->update(['read' => true]);

    $this->display = [
        'topic' => $topic,
        'workdate' => $msg->workday?->date->format('l d F Y'),
        'workername' => $msg->worker->full_name,
        'worktime' => $worktime ? $worktime : '',
        'remark' => $msg->remark,
        'replier' => $msg->replier,
        'response' => $msg->response,
    ];

    //Refresh messages volt component 
    $this->dispatch('refresh-data'); 
}]);

?>

<div class="flex justify-center items-center z-50 px-2" style="position:fixed; width:100vw; height:100vh; top:0; left:0; background:rgba(0,0,0,0.5);"
    x-show="showMessageModal" x-cloak
>
    <form wire:submit.prevent="save" class="bg-white p-6 mr-3 rounded-xl shadow-md w-[90%] max-w-lg relative" @click.away="showMessageModal = false">
        @php 
            $notHoliday = $this->message?->workday?->type != 'holiday';
        @endphp
        <div class="absolute top-[-15px] right-[-15px] text-xs text-white w-8 h-8 bg-gray-600 hover:bg-gray-800 rounded-full flex justify-center items-center 
            cursor-pointer shadow-md duration-200"
            @click="showMessageModal = false"
        >
            <i class="bi bi-x-lg"></i>
        </div>
        <div class="">

            <!-- Top header -->
            <h3 class="text-xl font-medium pb-1 mb-2 b-ah-border">
                @if($this->message?->topic != 4)
                    {{ $this->display['topic'] }}
                @else
                    <span>{{ $this->display['topic'] }} verzoek aan </span> 
                    <span class="color-ah">{{ $this->display['replier'] }}</span>
                @endif
            </h3>
            <div class="flex gap-2 font-light">
                <span><i class="bi bi-calendar4-week text-blue-400"></i></span>
                <span>{{ $this->display['workdate'] }}</span>
            </div>

            <!-- Shift information -->
            <div class="flex gap-2 font-light">
                <span class="text-blue-400 inline-block">
                    @if($notHoliday) <i class="bi bi-clock"></i>
                    @else <i class="bi bi-brightness-alt-high-fill"></i>
                    @endif
                </span>
                <span>{{ $this->display['worktime'] }}</span>
            </div>

            <!-- Worker request -->
            <div class="mt-4">{{ $this->display['workername'] }}:</div></div>
            <div class="bg-gray-100 rounded-md p-2 inline-block font-light">
                {{ $this->display['remark'] }}
            </div>

            <!-- Leader response -->
            @if($this->message?->response)
                <div class="flex flex-col items-end">
                    <div class="mt-4">{{$this->display['replier']}}:</div>
                    <div class="bg-gray-100 rounded-md p-2 inline-block font-light">
                        {{ $this->display['response'] }}
                    </div>
                </div>
            @endif

            <!-- Bottom status -->
            <h3 class="text-xl font-medium mt-6 pt-1 t-border">Status</h3>
            @if($this->message?->status == 1)
                <div class="flex gap-1 text-blue-400">
                    <span>Goedgekeurd</span>
                    <i class="bi-check2-circle"></i>
                </div>
            @elseif($this->message?->status == 2)
                <div class="flex gap-1 text-amber-600">
                    <span>Afgekeurd</span>
                    <i class="bi bi bi-x-circle"></i>
                </div>
            @else
                <div class="flex gap-1 text-blue-400">
                    <span>In behandeling...</span>
                    <i class="bi bi-hourglass-split inline-block animate-spin-pause-ease"></i>
                </div>
            @endif
        </div>
    </form>
</div>

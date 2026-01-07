<?php

use function Livewire\Volt\{state, mount, computed, on};
use App\Models\Workday;
use App\Models\Message;
use Carbon\Carbon;
//
state([
    'message' => fn() => Workday::value('worker_id'),
    'options' => Workday::getTopicOptions(),
]);

mount(function (Workday $workday) {
    // This goes into the Worker model and grabs the 'name' column
    // dd($workday->first());
    // $this->workerName = $workday->worker?->first_name ?? '?';
});

on(['refresh-data' => '$refresh']);

$messages = computed(function () {
    $getWorker = \App\Models\Worker::first();
    if($getWorker){
        return Message::query()
            ->where('worker_id', $getWorker->id)
            ->orderBy('created_at', 'desc') // Shortcut for orderBy('updated_at', 'desc')
            ->get();
    }else return abort(404, 'No record found in the database.');
    
});

$categorizePeople = computed( function(){

    //Categorize their names position
    return collect($people)->map(function($fullname){
        $parts = preg_split('/\s+/', trim($fullname)); //Removing any whitespace
        $count = count($parts);
        return [
            'firstname' => $parts[0],
            'middlename' => ($count > 2 ? array_slice($parts, 1, -1) : ''), //Take middle part
            'lastname' => ($count > 1 ? end($parts) : ''), //Take last part
            'fullname' => implode(' ', $parts),
            'user_id' => strtoupper(uniqid()),
        ];
    });

});

?>

<div class="mb-10 flex justify-center" x-data="{ showMessageModal: false }">

    @if($this->messages()->count() > 0)

        <div wire:poll.10s class="flex flex-col border rounded-xl overflow-hidden cursor-pointer">
    
            <div class="flex bg-gray-100 border-b border-gray-200 font-bold text-base">
                <div class="w-[10%] p-3 overflow-hidden">Status</div>
                <div class="w-[65%] p-3 ml-1">Onderwerp</div>
                <div class="w-[25%] p-3 ml-1">Verstuurd</div>
            </div>

            <!-- Message item -->
            @foreach($this->messages() as $message)
                <div class="flex bg-white border-b last:border-b-0 hover:bg-gray-50 transition"
                    @click="$dispatch('set-modal-messsage-data', 
                    { 
                        messageId: '{{ $message->id }}',
                        topic: '{{ $this->options[$message->topic]['label'] }}',
                    }),
                    showMessageModal = true"
                >
                    <!-- Status icon -->
                    <div class="w-[10%] p-3 text-sm">
                        @if($message->status == 1)
                            <div class="rounded-full w-full shrink-0 text-indigo-400 flex justify-center items-center">
                                <i class="text-lg bi-check2-circle"></i>
                            </div>
                        @elseif($message->status == 2)
                            <div class="rounded-full w-full shrink-0 text-amber-600 flex justify-center items-center">
                                <i class="text-base bi bi-x-circle"></i>
                            </div>
                        @else
                            <div class="rounded-full w-full shrink-0 text-gray-400 flex justify-center items-center">
                                <i class="text-lg bi-hourglass-split inline-block animate-spin-pause-ease"></i>
                            </div>
                        @endif
                    </div>

                    <!-- Message content -->
                    <div class="w-[65%] p-4 text-sm line-clamp-2">
                        [{{$this->options[$message->topic]['label']}}] {{$message->remark}}
                    </div>

                    <!-- Date display -->
                    <div class="w-[25%] p-4 text-sm">
                        <span class="p-1 rounded-full text-xs font-light">
                            {{$message->updated_at->format('d-M-Y, H:i')}}
                        </span>
                    </div>

                </div>
            @endforeach
        </div>

    @else

        <!-- Empty display -->
        <div class="w-full max-w-3xl rounded-lg p-4 bg-gray-200 text-center ">
            Geen berichten nog.
        </div>
    @endif

    <section class="p-2">
        <livewire:message-modal />
    </section>
</div>

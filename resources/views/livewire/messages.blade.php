<?php

use function Livewire\Volt\{state, mount, computed, on, uses, with};
use Livewire\WithPagination;
use App\Models\Workday;
use App\Models\Message;
use Carbon\Carbon;


state([
	'message' => fn() => Workday::value('worker_id'),
	'options' => Workday::getTopicOptions(),
	'page' => 1,
])->url();

// Keep track of current page number from the pagination
$refreshMessages = function ($currentPage = null) {
   ($currentPage) ? $this->page = (int) $currentPage : 1;
};

// Grab all corresponding messages
$messages = computed(function () {
    $getWorker = \App\Models\Worker::first();
    if($getWorker){
        return Message::query()
            ->where('worker_id', $getWorker->id)
            ->orderBy('created_at', 'desc') // Shortcut for orderBy('updated_at', 'desc')
            ->paginate(8, ['*'], 'page', $this->page) // Force the page to stick to the pagenumber
				->withPath('/'); // Prevents the URL from being confused
    }else return abort(404, 'No record found in the database.');
    
});

$totalCount = computed(function () {
	return Message::count();
});

?>

<div class="mb-2 sm:max-w-[650px] py-10 mx-auto" x-data="{ showMessageModal: false }"
		x-on:refresh-data.window="
		const urlParams = new URLSearchParams(window.location.search);
		const currentPage = urlParams.get('page') || 1;
		$wire.refreshMessages(currentPage);
">

	<section wire:poll.8s>

		<!-- Breadcrumbs -->
		<div class="flex items-center mb-8">
			<a href="{{route('calendar')}}">Werkrooster</a>
			<i class="bi bi-chevron-right text-xs mx-2 stroke-1"></i>
			<span class="text-gray-400">Berichten</span>
		</div>

		<h3 class="text-lg font-bold text-center mt-8 mb-2">Mijn berichten ({{ $this->totalCount }})</h3>

		@if($this->messages()->count() > 0)
		<div wire:key="message-list" class="flex flex-col border border-gray-300 rounded-xl overflow-hidden cursor-pointer">

			<div class="flex gray-light font-bold text-base border-b border-gray-300">
				<div class="w-[10%] p-3 overflow-hidden">Status</div>
				<div class="w-[65%] p-3 ml-2">Onderwerp</div>
				<div class="w-[25%] p-3 ml-6">Verstuurd</div>
			</div>

			<!-- Message item -->
			@foreach($this->messages as $message)
			<div class="flex border-b border-gray-200 last:border-b-0 hover:bg-gray-50 transition {{$message->read ? 'bg-white' : 'gray-light font-medium'}}" 
					@click="$dispatch('set-modal-message-data', 
					{ 
						messageId: '{{ $message->id }}',
						topic: '{{ $this->options[$message->topic]['label'] }}',
					}),
					showMessageModal = true">

				<!-- Status icon -->
				<div class="w-[10%] p-3 text-sm flex justify-center items-center">
					@if($message->status == 1)
					<div class="rounded-full w-full shrink-0 text-indigo-400 text-center">
						<i class="text-lg bi-check2-circle"></i>
					</div>
					@elseif($message->status == 2)
					<div class="rounded-full w-full shrink-0 text-amber-600 text-center">
						<i class="text-base bi bi-x-circle"></i>
					</div>
					@else
					<div class="rounded-full w-full shrink-0 text-gray-400 text-center">
						<i class="text-lg bi-hourglass-split inline-block animate-spin-pause-ease"></i>
					</div>
					@endif
				</div>

				<!-- Message content -->
				<div class="w-[65%] p-4 text-sm line-clamp-2">
					<span>[{{$this->options[$message->topic]['label']}}]</span>
					@if($message->topic == 4) 
					<span>{{ $this->options[$message->topic]['description'] }}:</span>
					<span>{{ $message->replier }}.</span>
					@else
					<span>{{$message->remark}}</span>
					@endif
					</span>
					
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

		<!-- Pagination -->
		<div class="mt-3 mx-auto" wire:key="messages.pagination">
			{{ $this->messages->links() }}
		</div>

		@else

		<!-- Empty display -->
		<div class="w-full max-w-3xl rounded-lg p-4 bg-gray-200 text-center ">
			Geen berichten nog.
		</div>
		@endif

	</section>

	<section class="p-2">
		<livewire:message-modal />
	</section>
</div>
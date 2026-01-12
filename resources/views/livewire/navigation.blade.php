<?php

use function Livewire\Volt\{state, computed};

state([
	'open' => false,
]);

//Fetch user ID
$user = computed(function () {
	$worker = \App\Models\Worker::first();
	return ($worker ? $worker->full_name : '');
});

$messagesCount = computed(function() {
	return \App\Models\Message::where('worker_id', 1)->get()->count();
});

?>
<nav x-data="{ open: @entangle('open') }"
	class="w-full relative flex justify-between flex-wrap bg-[#00A2E5] py-2 px-3">

	<div class="flex flex-col md:flex-row items-center gap-x-7">

		<div class="flex items-center text-white mr-2">
			<img src="{{asset('images/AH-Planner-logo-white.png')}}" alt="App logo" class="h-[50px] md:h-[40px] w-auto">
			<span class="font-semibold text-[24px] md:text-xl ml-1 ">Planning</span>
		</div>

		<div :class="open ? 'flex' : 'hidden'" 
     		class="md:flex lg:flex-grow flex-wrap items-start md:items-center flex-col md:flex-row gap-x-6 gap-y-2">
     
			<a href="{{ route('calendar') }}" class="flex items-center cursor-pointer pt-2 gap-1 md:gap-[2px] md:pt-0"
				x-data="{ hovering: false }" 
				@mouseenter="hovering = true" 
				@mouseleave="hovering = false">
				<i x-show="!hovering" class="bi bi-calendar3-week text-white text-xl md:text-lg"></i>
				<i x-show="hovering" class="bi bi-calendar3 text-white text-xl md:text-lg" x-cloak></i>
				<span class="ml-1 text-white text-lg md:text-base">Werkrooster</span>
			</a>

			<a href="{{ route('swap') }}" class="flex items-center cursor-pointer gap-1 md:gap-[2px] group">
				<i class="bi bi-arrow-repeat text-white text-[22px] inline-block transition-transform duration-500 group-hover:rotate-180"></i>
				<span class="ml-1 text-white text-lg md:text-base">Shift ruilen</span>
			</a>

			<a href="{{ route('messages') }}" class="flex items-center cursor-pointer pb-2 gap-1 md:gap-[2px] md:pb-0"
				x-data="{ hovering: false }" 
				@mouseenter="hovering = true" 
				@mouseleave="hovering = false">
				<i x-show="!hovering" class="bi bi-envelope text-white text-xl md:text-xl"></i>
				<i x-show="hovering" class="bi bi-envelope-open text-white text-xl md:text-xl" x-cloak></i>
				<span class="ml-1 text-white text-lg md:text-base">Berichten ({{ $this->messagesCount }})</span>
			</a>
		</div>

	</div>
				
	<div class="pt-2 md:pt-0 flex gap-3">

		<div class="block">
			<a href="#"
				class="text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white mt-[2px] lg:mt-0 hidden xs:block">
				{{$this->user}}
			</a>
		</div>

		<div class="block md:hidden">
			<button @click="open = !open"
				class="flex items-center px-2 py-1 border rounded text-teal-200 border-teal-400 hover:text-white hover:border-white">
				<i class="bi" :class="open ? 'bi-x-lg' : 'bi-list'"></i>
			</button>
		</div>

	</div>

</nav>
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

?>
<nav x-data="{ open: @entangle('open') }"
	class="w-full relative flex items-center justify-between flex-wrap bg-[#00A2E5] p-2 mb-6">
	<div class="flex items-center flex-shrink-0 text-white mr-10">
		<img style="height:40px; width: auto;" src="{{asset('images/AH-Planner-logo-white.png')}}" alt="App logo">
		<span class="font-semibold text-xl ml-1 tracking-tight">Planning</span>
	</div>

	<div :class="{'hidden': !open, 'block': open}" class="w-full block flex-grow lg:flex lg:items-center lg:w-auto">
		<div class="text-sm lg:flex-grow">
			<a href="#responsive-header" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-white mr-4">
				Werkrooster
			</a>
			<a href="#responsive-header" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-white mr-4">
				Examples
			</a>
			<a href="#responsive-header" class="block mt-4 lg:inline-block lg:mt-0 text-white hover:text-white">
				Blog
			</a>
		</div>
	</div>

	<!--  -->
	<div class="absolute top-0 right-5 p-2" style="height:inherit;">
		<div class="flex items-center gap-3">
			<div class="mt-[2px]">
				<a href="#"
					class="inline-block text-sm px-4 py-2 leading-none border rounded text-white border-white hover:border-transparent hover:text-teal-500 hover:bg-white lg:mt-0">
					{{$this->user}}
				</a>
			</div>
			<div class="block lg:hidden">
				<button @click="open = !open"
					class="flex items-center px-2 py-1 border rounded text-teal-200 border-teal-400 hover:text-white hover:border-white">
					<i class="bi" :class="open ? 'bi-x-lg' : 'bi-list'"></i>
				</button>
			</div>
		</div>
	</div>

</nav>
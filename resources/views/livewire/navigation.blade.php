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

		<!-- Logo -->
		<a href="{{ route('home') }}" navigate:true class="flex items-center text-white mr-2">
			
			<div wire:persist="main-logo" class="w-[40px] h-[40px]">
				<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
					width="100%" height="100%" viewBox="0 0 600 600" enable-background="new 0 0 600 600" xml:space="preserve">
					<path fill="#FFFFFF" d="M307.146,576.712c-17.288,0-31.308-14.02-31.308-31.31c0-17.288,14.02-31.309,31.308-31.309
						c117.516,0,213.118-95.602,213.118-213.119c0-117.518-95.603-213.123-213.118-213.123c-117.518,0-213.126,95.605-213.126,213.123
						c0,55.083,20.922,107.303,58.928,147.125c14.412,13.541,28.339,13.049,37.051,8.567c6.225-3.181,12.392-11.081,12.693-18.585
						c1.249-31.12,5.91-27.353,23.041-29.681c17.201-2.339,47.774,11.985,47.658,29.269c-0.206,29.786-31.085,67.178-61.919,77.927
						c-35.587,12.407-82.56,1.289-102.193-22.614l-0.989-0.984c-49.58-51.604-76.887-119.445-76.887-191.023
						c0-152.045,123.698-275.74,275.743-275.74c152.042,0,275.736,123.695,275.736,275.74
						C582.883,453.018,459.188,576.712,307.146,576.712L307.146,576.712z"/>

					<path fill="#FFFFFF" d="M273.392,437.67v-75.196c11.982,17.337,32.019,26.006,60.092,26.006c27.739,0,50.274-8.829,67.596-26.489
						c17.323-17.667,25.982-43.899,25.982-78.705c0-32.715-8.581-57.646-25.737-74.785c-17.154-17.139-39.19-25.716-66.117-25.716
						c-31.198,0-53.034,11.018-65.509,33.054v-28.94h-67.476v264.152L273.392,437.67z M273.392,268.615
						c0-16.549,4.022-28.277,12.073-35.188c8.037-6.909,16.989-10.362,26.84-10.362c25.938,0,38.908,19.49,38.908,58.464
						c0,27.762-3.943,45.729-11.821,53.861c-7.876,8.155-16.832,12.225-26.842,12.225c-8.047,0-15.512-2.117-22.412-6.354
						c-6.892-4.236-11.411-9.027-13.545-14.376c-2.13-5.337-3.202-14.981-3.202-28.934V268.615z"/>
					
				</svg>
			</div>
			<span class="font-semibold text-[24px] md:text-xl ml-1 ">Planning</span>
		</a>

		<!-- Main links -->
		<div :class="open ? 'flex' : 'hidden'" 
     		class="md:flex lg:flex-grow flex-wrap items-start md:items-center flex-col md:flex-row gap-x-6 gap-y-2">
     
			<a href="{{ route('calendar') }}" navigate: true  
					class="flex items-center cursor-pointer pt-2 gap-1 md:gap-[2px] md:pt-0"
					x-data="{ hovering: false }" 
					@mouseenter="hovering = true" 
					@mouseleave="hovering = false">
				<i x-show="!hovering" class="bi bi-calendar3-week text-white text-xl md:text-lg"></i>
				<i x-show="hovering" class="bi bi-calendar3 text-white text-xl md:text-lg" x-cloak></i>
				<span class="ml-1 text-white text-lg md:text-base">Werkrooster</span>
			</a>

			<a href="{{ route('swap') }}" navigate: true class="flex items-center cursor-pointer gap-1 md:gap-[2px] group">
				<i class="bi bi-arrow-repeat text-white text-[22px] inline-block transition-transform duration-500 group-hover:rotate-180"></i>
				<span class="ml-1 text-white text-lg md:text-base">Shift ruilen</span>
			</a>

			<a href="{{ route('messages') }}" navigate: true
					class="flex items-center cursor-pointer pb-2 gap-1 md:gap-[2px] md:pb-0"
					x-data="{ hovering: false }" 
					@mouseenter="hovering = true" 
					@mouseleave="hovering = false">
				<i x-show="!hovering" class="bi bi-envelope text-white text-xl md:text-xl"></i>
				<i x-show="hovering" class="bi bi-envelope-open text-white text-xl md:text-xl" x-cloak></i>
				<span class="ml-1 text-white text-lg md:text-base">Berichten ({{ $this->messagesCount }})</span>
			</a>
		</div>

	</div>
				
	<!-- Menu & User -->
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
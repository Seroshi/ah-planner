<?php

use function Livewire\Volt\{state, on};

state([
    'message' => null,
    'activeModal' => null
]);

$showDetails = fn() => $this->activeModal = 'details';
$showShiftSwap = fn() => $this->activeModal = 'shiftSwap';
$close = fn() => $this->activeModal = null;

on(['set-modal-shift-swap-data' => function($shiftId){
    $this->message = $shiftId;
}]);

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
            <h3>Testing!</h3>
        </div>
    </form>

</div>


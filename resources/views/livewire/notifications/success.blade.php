<?php

use function Livewire\Volt\{state};

//

?>

<div class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded shadow-lg"
    x-data="{ show: @js(session()->has('notification')) }" 
    x-show="show" 
    x-init="if(show) setTimeout(() => show = false, 5000)"
>
    @if(session()->has('notification'))
        <div class="alert alert-success">
            {{ session('notification') }}
        </div>
    @endif
</div>
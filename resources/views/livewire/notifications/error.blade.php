<?php

use function Livewire\Volt\{state};

//

?>

<div>
    @if(session()->has('error'))
    <div class="bg-red-100 text-red-700 px-6 py-3 rounded shadow-lg">
        <div class="alert alert-error text-center">
            {{ session('error') }}
        </div>
    </div>
    @endif
</div>

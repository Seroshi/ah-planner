<?php

use function Livewire\Volt\{state};

//

?>

<div x-data="{ show: false, message: '' }"
    @success.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 5000)"
    x-show="show"
    x-transition
    class="fixed bottom-5 right-5 bg-green-600 text-white px-6 py-3 rounded shadow-lg">
    <span x-text="message"></span>
</div>
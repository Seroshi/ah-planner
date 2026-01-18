<?php

use function Livewire\Volt\{state, mount, rules, computed};
use Illuminate\Support\Facades\Cache;
use App\Models\Visitor;


state([
    'formFields' => [],
    'processing' => false,
    'visitor' => null,
]);

mount(function(){

    // Redirect if it's a returning visitor in this session
    if( visitor() ) return $this->redirectRoute('calendar', navigate: true);

    // Defining the form fields
    $this->formFields = [
        'firstName' => '',
        'middlePart' => '',
        'lastName' => '',
    ];
});


$save = function(){

    // Setup for the validation rules and messages
    $this->validate(
        [ // Rules:
            'formFields.firstName' => 'required',
            'formFields.lastName' => 'required'
        ],
        [ // Messages:
            'formFields.firstName.required' => 'verplicht',
            'formFields.lastName.required' => 'verplicht'
        ],
    );

    // Create new visitor in the database and remember in session
    if( !visitor() ){

        $visitor = Visitor::create([
            'first_name' => $this->formFields['firstName'],
            'middle_part' => $this->formFields['middlePart'],
            'last_name' => $this->formFields['lastName'],
            'session_id' => session()->getId(),
            'active' => true,
        ]);

        session(['active_visitor_id' => $visitor->id]);

    }

    // Start the loading spinner 
    $this->processing = true;

    // Try to get a lock for 30 seconds
    $lock = Cache::lock('db_reset', 30);

    if ($lock->get()) {
        try {

            // Clear specific tables and run the worker seeder
            Artisan::call('db:seed', ['--force' => true]);

            // 2. Add a tiny artificial pause (4 seconds)
            usleep(3000000);

            // Ensure the visitor has already initialized the project in this session
            session(['demo_initialized' => true]);

            return $this->redirectRoute('calendar', navigate: true);

        }
        catch (\Exception $e) {
            session()->flash('error', 'Database reset failed: ' . $e->getMessage());
        } finally {
            // ALWAYS release the lock so the next person isn't blocked
            $lock->release();
        }
    } else {
        session()->flash('error', 'Server is druk, probeer het over 10 seconden opnieuw.');
    }
    // Artisan::call('db:seed', ['--force' => true]);

    // return $this->redirectRoute('calendar', navigate: true);
};

?>

<div class="flex justify-center items-center z-50 fixed top-0 left-0 w-screen h-screen" style="background:rgba(0,0,0,0.5);">

    <section x-data="{processing: @entangle('processing') }" class="bg-white p-6 mr-3 rounded-xl shadow-md max-w-xl overflow-hidden relative">

        <div x-show="processing" style="display: none;" class="absolute inset-0 flex flex-col gap-3 text-center items-center justify-center bg-gray-100 z-10">
            <img src="{{ asset('images/favicon.svg') }}" alt="logo" class="w-[80px] h-auto animate-pulse">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-blue-200"></div>
            <div class="text-blue-400 font-bold">Een moment geduld a.u.b.</br> De data wordt voor je klaargezet.</div>
        </div>

        @session('error')  
            <div class="bg-red-100 text-red-700 p-4 mb-5 flex items-center justify-center gap-2">
                <span><i class="bi bi-exclamation-diamond text-xl"></i></span>
                <span>{{ session('error') }}</span>
            </div>
        @endsession

        <div class="flex gap-3 items-center mb-8">
            <img src="{{ asset('images/favicon.svg') }}" alt="logo" class="w-[80px] h-auto">
            <div>
                <h3 class="font-bold text-xl">AH Planner</h3>
                <div>Welkom nieuwe collega!</div>
            </div>
        </div>

        <p class="mb-6">Om je goed van dienst te zijn, hebben we de volgende gegevens van je nodig:</p>

        <form wire:submit.prevent="save" x-on:submit="processing = true">

            <div class="flex gap-x-3 flex-col xs:flex-row">
                <div class="mb-4">
                    <label class="text-base font-bold">Voornaam * 
                        @error('formFields.firstName')<span class="text-red-600 font-light">{{ $message }}</span>@enderror
                    </label>
                    <input wire:model="formFields.firstName" type="text" class="w-full bg-gray-100 rounded-md focus:outline focus:outline-blue-600 mt-2 p-2">
                </div>

                <div class="mb-4">
                    <label class="text-base font-bold" for="middlePart">Tussenvoegsel</label>
                    <input wire:model="formFields.middlePart" type="text" class="w-full bg-gray-100 rounded-md focus:outline focus:outline-blue-600 mt-2 p-2">
                </div>
            </div>

            <div class="mb-6">
                <label class="text-base font-bold" for="lastName">Achternaam *
                    @error('formFields.lastName')<span class="text-red-600 font-light">{{ $message }}</span>@enderror
                </label>
                <input wire:model="formFields.lastName" type="text" class="w-full bg-gray-100 rounded-md focus:outline focus:outline-blue-600 mt-2 p-2">
            </div>

            <button type="submit" class="btn bg-ah bg-ah-hover py-2 w-full text-white text-center">
                <span>Bevestig </span>
                <i class="bi bi-check2-circle"></i>
            </button>

        </form>

    </section>

</div>

@extends('components.layouts.master')

@section('title')  
    Werkrooster
@endsection

@section('content')

    <div class="w-full px-8 py-2 sm:px-10">
        <livewire:messages />
    </div>

    <div class="w-full px-8 sm:px-10">
        <livewire:calendar />
    </div>

    <section>
        <livewire:notifications.success />
    </section>

@endsection
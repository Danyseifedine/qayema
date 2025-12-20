<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(!auth()->user()->isProfileComplete())
                @php
                    $currentStep = 1;
                @endphp
                <x-wizard-steps :currentStep="$currentStep" />
            @elseif(auth()->user()->menus()->count() == 0)
                @php
                    $currentStep = 2;
                @endphp
                <x-wizard-steps :currentStep="$currentStep" />
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
                    <!-- Statistics will be added here -->
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


@props(['currentStep'])

@php
$steps = [
    1 => ['title' => 'Complete Profile', 'route' => 'profile.edit', 'description' => 'Add restaurant details, logo & cover image'],
    2 => ['title' => 'Create Menu', 'route' => 'menu-owner.menus.index', 'description' => 'Create your first menu'],
];
@endphp

<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Getting Started</h3>
    <div class="space-y-4">
        @foreach($steps as $stepNumber => $step)
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    @if($stepNumber < $currentStep)
                        <!-- Completed step -->
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-500 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    @elseif($stepNumber == $currentStep)
                        <!-- Current step -->
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white font-semibold">
                            {{ $stepNumber }}
                        </div>
                    @else
                        <!-- Upcoming step -->
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 text-gray-600 font-semibold">
                            {{ $stepNumber }}
                        </div>
                    @endif
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center">
                        @if($stepNumber < $currentStep)
                            <a href="{{ route($step['route']) }}" class="text-lg font-medium text-green-600 hover:text-green-700">
                                {{ $step['title'] }}
                            </a>
                        @elseif($stepNumber == $currentStep)
                            <span class="text-lg font-medium text-indigo-600">{{ $step['title'] }}</span>
                        @else
                            <span class="text-lg font-medium text-gray-500">{{ $step['title'] }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $step['description'] }}</p>
                    @if($stepNumber == $currentStep)
                        <div class="mt-2">
                            @if($stepNumber == 1)
                                <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Complete Profile
                                </a>
                            @elseif($stepNumber == 2)
                                <a href="{{ route('menu-owner.menus.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Create Menu
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @if($stepNumber < count($steps))
                <div class="ml-5 border-l-2 border-gray-200 h-6"></div>
            @endif
        @endforeach
    </div>
</div>


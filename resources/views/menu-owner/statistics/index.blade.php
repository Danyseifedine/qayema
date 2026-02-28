<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('menu_owner.statistics.title') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (!$menu)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-gray-600">{{ __('menu_owner.statistics.create_menu_first') }}</p>
                        <a href="{{ route('menu-owner.menus.index') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('menu_owner.common.create') }} {{ __('menu_owner.menus.title') }}
                        </a>
                    </div>
                </div>
            @else
                <!-- Menu Link Copy Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('menu_owner.statistics.menu_link') }}</h3>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="menuUrl" readonly
                                        value="{{ $menuUrl }}"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <button type="button" onclick="copyMenuLink()"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        {{ __('menu_owner.common.copy_link') }}
                                    </button>
                                </div>
                                <p id="copyMessage" class="mt-2 text-sm text-green-600 hidden">{{ __('menu_owner.common.link_copied') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total Visits (sessions) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.total_visits') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ __('menu_owner.statistics.total_visits_desc') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($totalViews) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unique Visitors -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.unique_visitors') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ __('menu_owner.statistics.unique_visitors_desc') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($uniqueVisitors) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Page Views -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.total_page_views') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ __('menu_owner.statistics.total_page_views_desc') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($totalPageViews) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Time Spent -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.avg_time_spent') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ __('menu_owner.statistics.avg_time_spent_desc') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        @if ($averageTimeSpent > 0)
                                            @php
                                                $minutes = floor($averageTimeSpent / 60);
                                                $seconds = $averageTimeSpent % 60;
                                            @endphp
                                            @if ($minutes > 0)
                                                {{ $minutes }}m {{ $seconds }}s
                                            @else
                                                {{ $seconds }}s
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('menu_owner.statistics.sessions_with_recorded_exit', ['count' => number_format($sessionsWithTimeSpent)]) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page Views Today -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.page_views_today') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ __('menu_owner.statistics.page_views_today_desc') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($viewsToday) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Views This Week -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-teal-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-teal-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.page_views_this_week') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($viewsThisWeek) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Page Views This Month -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-pink-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-pink-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.page_views_this_month') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($viewsThisMonth) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bounce Rate -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.bounce_rate') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ __('menu_owner.statistics.bounce_rate_desc') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $bounceRate }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Statistics -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
                    <!-- Device Breakdown -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Device Breakdown</h3>
                            @if (!empty($deviceBreakdown))
                                <div class="space-y-3">
                                    @foreach ($deviceBreakdown as $device => $count)
                                        @php
                                            $percentage = $totalViews > 0 ? round(($count / $totalViews) * 100, 1) : 0;
                                            $deviceColors = [
                                                'mobile' => 'bg-blue-500',
                                                'tablet' => 'bg-purple-500',
                                                'desktop' => 'bg-green-500',
                                            ];
                                            $color = $deviceColors[$device] ?? 'bg-gray-500';
                                        @endphp
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-sm font-medium text-gray-700 capitalize">{{ $device }}</span>
                                                <span class="text-sm text-gray-500">{{ number_format($count) }} ({{ $percentage }}%)</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="{{ $color }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No device data available</p>
                            @endif
                        </div>
                    </div>

                    <!-- Browser Breakdown -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Browsers</h3>
                            @if (!empty($browserBreakdown))
                                <div class="space-y-3">
                                    @foreach ($browserBreakdown as $browser => $count)
                                        @php
                                            $percentage = $totalViews > 0 ? round(($count / $totalViews) * 100, 1) : 0;
                                        @endphp
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-sm font-medium text-gray-700">{{ $browser }}</span>
                                                <span class="text-sm text-gray-500">{{ number_format($count) }} ({{ $percentage }}%)</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No browser data available</p>
                            @endif
                        </div>
                    </div>

                    <!-- OS Breakdown -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Operating Systems</h3>
                            @if (!empty($osBreakdown))
                                <div class="space-y-3">
                                    @foreach ($osBreakdown as $os => $count)
                                        @php
                                            $percentage = $totalViews > 0 ? round(($count / $totalViews) * 100, 1) : 0;
                                        @endphp
                                        <div>
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-sm font-medium text-gray-700">{{ $os }}</span>
                                                <span class="text-sm text-gray-500">{{ number_format($count) }} ({{ $percentage }}%)</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-teal-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-sm">No OS data available</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Additional Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <!-- Average Page Views per Session -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-cyan-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-cyan-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.avg_page_views_per_session') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ $avgPageViewsPerSession }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Time Spent -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-orange-100 rounded-md p-3">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ __('menu_owner.statistics.total_time_spent') }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        @if ($totalTimeSpent > 0)
                                            @php
                                                $hours = floor($totalTimeSpent / 3600);
                                                $minutes = floor(($totalTimeSpent % 3600) / 60);
                                            @endphp
                                            @if ($hours > 0)
                                                {{ $hours }}h {{ $minutes }}m
                                            @else
                                                {{ $minutes }}m
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('menu_owner.statistics.total_time_spent_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Visitors Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('menu_owner.statistics.recent_visitors') }}</h3>
                        @if ($statistics->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('menu_owner.statistics.viewed_at') }}
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('menu_owner.statistics.device') }}
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('menu_owner.statistics.time_spent') }}
                                            </th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ __('menu_owner.statistics.page_views') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($statistics as $stat)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $stat->viewed_at->format('M d, Y H:i') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $stat->device_type ?? 'Unknown' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    @if ($stat->time_spent)
                                                        @php
                                                            $minutes = floor($stat->time_spent / 60);
                                                            $seconds = $stat->time_spent % 60;
                                                        @endphp
                                                        @if ($minutes > 0)
                                                            {{ $minutes }}m {{ $seconds }}s
                                                        @else
                                                            {{ $seconds }}s
                                                        @endif
                                                    @else
                                                        {{ __('menu_owner.common.n_a') }}
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $stat->page_views }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-gray-500">{{ __('menu_owner.statistics.no_visitors_yet') }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function copyMenuLink() {
            const menuUrlInput = document.getElementById('menuUrl');
            const copyMessage = document.getElementById('copyMessage');

            menuUrlInput.select();
            menuUrlInput.setSelectionRange(0, 99999); // For mobile devices

            try {
                navigator.clipboard.writeText(menuUrlInput.value).then(function() {
                    copyMessage.classList.remove('hidden');
                    setTimeout(function() {
                        copyMessage.classList.add('hidden');
                    }, 3000);
                });
            } catch (err) {
                // Fallback for older browsers
                document.execCommand('copy');
                copyMessage.classList.remove('hidden');
                setTimeout(function() {
                    copyMessage.classList.add('hidden');
                }, 3000);
            }
        }
    </script>
</x-app-layout>

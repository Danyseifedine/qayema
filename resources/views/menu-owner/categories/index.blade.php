<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('menu_owner.categories.title') }}
            </h2>
            @if ($menu)
                <a href="{{ route('menu-owner.categories.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('menu_owner.categories.add_category') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @if (!$menu)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
                    {{ __('menu_owner.categories.create_menu_first') }}
                    <a href="{{ route('menu-owner.menus.index') }}" class="underline ms-2">{{ __('menu_owner.common.go_to_menu') }}</a>
                </div>
            @elseif($categories->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                            </path>
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ __('menu_owner.categories.no_categories') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.categories.get_started') }}</p>
                        <div class="mt-6">
                            <a href="{{ route('menu-owner.categories.create') }}"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Create Category
                            </a>
                        </div>
                    </div>
                </div>
            @else
                @if ($menu)
                    <div class="mb-4 text-sm text-gray-600">
                        <span class="font-semibold">{{ $categories->count() }}</span> / <span
                            class="font-semibold">{{ $menu->category_limit }}</span> categories
                        @if ($menu->hasReachedCategoryLimit())
                            <span class="ml-2 text-red-600 font-semibold">(Limit reached)</span>
                        @else
                            <span class="ml-2 text-green-600">({{ $menu->getRemainingCategorySlots() }} remaining)</span>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($categories as $category)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                @if ($category->hasMedia('image'))
                                    <div class="mb-4">
                                        <img src="{{ $category->getFirstMediaUrl('image') }}"
                                            alt="{{ $category->name }}" class="w-full h-48 object-cover rounded-lg">
                                    </div>
                                @else
                                    <div class="mb-4 bg-gray-100 rounded-lg h-48 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif

                                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $category->name }}</h3>

                                @if ($category->description)
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $category->description }}</p>
                                @endif

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span>{{ $category->dishes()->count() }}
                                        {{ Str::plural('dish', $category->dishes()->count()) }}</span>
                                    <span>Order: {{ $category->display_order }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('menu-owner.categories.edit', $category) }}"
                                        class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Edit
                                    </a>
                                    <form action="{{ route('menu-owner.categories.destroy', $category) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

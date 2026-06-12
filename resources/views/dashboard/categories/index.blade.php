<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.categories') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.categories.title') }}</h1>
            @if ($restaurant && ! $restaurant->hasReachedCategoryLimit())
                <x-btn href="{{ route('menu-owner.categories.create') }}" variant="primary" size="sm" class="gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('menu_owner.categories.add_category') }}
                </x-btn>
            @endif
        </div>


        @if (! $restaurant)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                {{ __('menu_owner.categories.create_menu_first') }}
                <a href="{{ route('menu-owner.restaurant.index') }}" class="underline ms-2">{{ __('menu_owner.common.go_to_menu') }}</a>
            </div>
        @elseif($categories->isEmpty())
            <x-ui.empty
                title="{{ __('menu_owner.categories.no_categories') }}"
                description="{{ __('menu_owner.categories.get_started') }}"
                icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>'>
                <x-btn href="{{ route('menu-owner.categories.create') }}" variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('menu_owner.categories.create_category') }}
                </x-btn>
            </x-ui.empty>
        @else
            @if ($restaurant)
                <p style="font-size:13px;color:var(--muted);margin-bottom:20px">
                    {{ __('menu_owner.categories.limit_text', ['count' => $categories->count(), 'limit' => $restaurant->category_limit]) }}
                    @if ($restaurant->hasReachedCategoryLimit())
                        <span style="color:var(--danger);font-weight:600;margin-left:8px">{{ __('menu_owner.common.limit_reached') }}</span>
                    @else
                        <span style="color:var(--olive-deep);margin-left:8px">{{ __('menu_owner.common.remaining', ['count' => $restaurant->getRemainingCategorySlots()]) }}</span>
                    @endif
                </p>
            @endif

            <div x-data="reorderGrid('{{ route('menu-owner.categories.reorder') }}')"
                 @keydown.escape.window="selected = null">

                <div x-show="selected !== null" x-cloak class="reorder-hint">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                    {{ __('menu_owner.common.reorder_hint') }}
                    <button type="button" @click="selected = null" style="margin-left:auto;font-size:12px;color:var(--olive-deep);background:none;border:none;cursor:pointer;padding:0">{{ __('menu_owner.common.cancel') }}</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($categories as $category)
                    <div class="qp-card"
                         :class="{ 'ro-selected': selected === {{ $category->id }}, 'ro-target': selected !== null && selected !== {{ $category->id }} }"
                         @click="selected !== null && selected !== {{ $category->id }} ? swap({{ $category->id }}) : null">
                        @if ($category->hasMedia('image'))
                            <img src="{{ $category->getFirstMediaUrl('image') }}"
                                 alt="{{ $category->name }}"
                                 style="width:100%;height:180px;object-fit:cover;display:block">
                        @else
                            <div style="width:100%;height:180px;background:var(--paper-2, #EFE8DA);display:flex;align-items:center;justify-content:center">
                                <svg style="width:48px;height:48px;color:var(--muted-2)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <div class="qp-card-body">
                            <h3 style="font-size:15px;font-weight:600;color:var(--ink);margin:0 0 4px">{{ $category->name }}</h3>
                            <p style="font-size:12px;color:var(--muted)">{{ $category->dishes()->count() }} {{ Str::plural('dish', $category->dishes()->count()) }}</p>
                        </div>
                        <div class="qp-card-foot">
                            <x-btn href="{{ route('menu-owner.categories.edit', $category) }}" variant="secondary" size="sm" class="flex-1">{{ __('menu_owner.common.edit') }}</x-btn>
                            <button type="button" class="reorder-btn" :class="{ 'is-selected': selected === {{ $category->id }} }"
                                    @click.stop="selected === {{ $category->id }} ? selected = null : (selected !== null ? swap({{ $category->id }}) : selected = {{ $category->id }})"
                                    title="{{ __('menu_owner.common.reorder') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                            </button>
                            <form id="del-cat-{{ $category->id }}" action="{{ route('menu-owner.categories.destroy', $category) }}" method="POST" style="display:none">
                                @csrf @method('DELETE')
                            </form>
                            <x-btn type="button" variant="danger" size="sm"
                                @click.stop="$dispatch('del-ask', { message: '{{ __('menu_owner.common.confirm_delete_category') }}', formId: 'del-cat-{{ $category->id }}' })">
                                {{ __('menu_owner.common.delete') }}
                            </x-btn>
                        </div>
                    </div>
                @endforeach
                </div>
            </div>
        @endif
    </div>
</x-sidebar-layout>

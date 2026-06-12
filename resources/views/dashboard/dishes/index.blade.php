<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.dishes') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.dishes.title') }}</h1>
            @if ($restaurant && ! $restaurant->hasReachedDishLimit())
                <x-btn href="{{ route('menu-owner.dishes.create') }}" variant="primary" size="sm" class="gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('menu_owner.dishes.add_dish') }}
                </x-btn>
            @endif
        </div>


        @if (! $restaurant)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                {{ __('menu_owner.dishes.create_menu_first') }}
                <a href="{{ route('menu-owner.restaurant.index') }}" class="underline ms-2">{{ __('menu_owner.common.go_to_menu') }}</a>
            </div>
        @elseif($dishes->isEmpty())
            <x-ui.empty
                title="{{ __('menu_owner.dishes.no_dishes') }}"
                description="{{ __('menu_owner.dishes.get_started') }}"
                icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>'>
                <x-btn href="{{ route('menu-owner.dishes.create') }}" variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('menu_owner.dishes.create_dish') }}
                </x-btn>
            </x-ui.empty>
        @else
            @if ($restaurant)
                <p style="font-size:13px;color:var(--muted);margin-bottom:20px">
                    {{ __('menu_owner.dishes.limit_text', ['count' => $dishes->count(), 'limit' => $restaurant->dish_limit]) }}
                    @if ($restaurant->hasReachedDishLimit())
                        <span style="color:var(--danger);font-weight:600;margin-left:8px">{{ __('menu_owner.common.limit_reached') }}</span>
                    @else
                        <span style="color:var(--olive-deep);margin-left:8px">{{ __('menu_owner.common.remaining', ['count' => $restaurant->getRemainingDishSlots()]) }}</span>
                    @endif
                </p>
            @endif

            <div x-data="reorderGrid('{{ route('menu-owner.dishes.reorder') }}')"
                 @keydown.escape.window="selected = null">

                <div x-show="selected !== null" x-cloak class="reorder-hint">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                    {{ __('menu_owner.common.reorder_hint') }}
                    <button type="button" @click="selected = null" style="margin-left:auto;font-size:12px;color:var(--olive-deep);background:none;border:none;cursor:pointer;padding:0">{{ __('menu_owner.common.cancel') }}</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($dishes as $dish)
                    <div class="qp-card"
                         :class="{ 'ro-selected': selected === {{ $dish->id }}, 'ro-target': selected !== null && selected !== {{ $dish->id }} }"
                         @click="selected !== null && selected !== {{ $dish->id }} ? swap({{ $dish->id }}) : null">
                        @if ($dish->hasMedia('images'))
                            <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                                 style="width:100%;height:180px;object-fit:cover;display:block">
                        @else
                            <div style="width:100%;height:180px;background:var(--paper-2, #EFE8DA);display:flex;align-items:center;justify-content:center">
                                <svg style="width:48px;height:48px;color:var(--muted-2)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3v8a2 2 0 002 2h0a2 2 0 002-2V3M10 13v8M16 3c-1 0-2 1-2 3v6h2v9"/>
                                </svg>
                            </div>
                        @endif
                        <div class="qp-card-body">
                            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:4px">
                                <h3 style="font-size:15px;font-weight:600;color:var(--ink);margin:0">{{ $dish->name }}</h3>
                                @if ($dish->is_available)
                                    <span class="dp-pill live" style="flex-shrink:0"><span class="dot"></span> {{ __('menu_owner.common.available') }}</span>
                                @else
                                    <span class="dp-pill hidden-pill" style="flex-shrink:0"><span class="dot"></span> {{ __('menu_owner.common.unavailable') }}</span>
                                @endif
                            </div>
                            @if ($dish->category)
                                <p style="font-size:12.5px;color:var(--olive-deep);margin:0 0 6px">{{ $dish->category->name }}</p>
                            @endif
                            @if ($dish->price)
                                <p style="font-size:16px;font-weight:600;color:var(--ink);margin:0;font-feature-settings:'tnum'">{{ \Illuminate\Support\Number::currency((float) $dish->price, $restaurant->currency ?? 'USD') }}</p>
                            @endif
                        </div>
                        <div class="qp-card-foot">
                            <x-btn href="{{ route('menu-owner.dishes.edit', $dish) }}" variant="secondary" size="sm" class="flex-1">{{ __('menu_owner.common.edit') }}</x-btn>
                            <button type="button" class="reorder-btn" :class="{ 'is-selected': selected === {{ $dish->id }} }"
                                    @click.stop="selected === {{ $dish->id }} ? selected = null : (selected !== null ? swap({{ $dish->id }}) : selected = {{ $dish->id }})"
                                    title="{{ __('menu_owner.common.reorder') }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4 4 4M17 8v12m0 0 4-4m-4 4-4-4"/></svg>
                            </button>
                            <form id="del-dish-{{ $dish->id }}" action="{{ route('menu-owner.dishes.destroy', $dish) }}" method="POST" style="display:none">
                                @csrf @method('DELETE')
                            </form>
                            <x-btn type="button" variant="danger" size="sm"
                                @click.stop="$dispatch('del-ask', { message: '{{ __('menu_owner.common.confirm_delete_dish') }}', formId: 'del-dish-{{ $dish->id }}' })">
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

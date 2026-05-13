<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        @if($restaurant)
            <span>{{ $restaurant->name }}</span>
            <span class="sbl-crumb-sep">/</span>
        @endif
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.dashboard') }}</span>
    </x-slot>
@php
    $activeDishCount = $dishes->where('is_available', true)->count();
    $totalDishCount  = $dishes->count();

    $dishesJson = $dishes->map(fn($d) => [
        'id'       => $d->id,
        'name'     => $d->name,
        'cat'      => $d->category?->name,
        'cat_id'   => $d->category_id,
        'price'    => number_format((float) $d->price, 2),
        'available'=> (bool) $d->is_available,
        'image'    => $d->getFirstMediaUrl('images'),
        'edit_url' => route('menu-owner.dishes.edit', $d),
    ])->values();

    $catsJson = $categories->map(fn($c) => [
        'id'    => $c->id,
        'name'  => $c->name,
        'count' => $c->dishes_count,
    ])->values();
@endphp

<div class="dash-page">
    <div class="dash-inner"
         x-data="{
             tab:    'all',
             cat:    null,
             dishes: @js($dishesJson),
             cats:   @js($catsJson),
             get filtered() {
                 return this.dishes.filter(d => {
                     const byTab = this.tab === 'all'
                         || (this.tab === 'active'  &&  d.available)
                         || (this.tab === 'hidden'  && !d.available);
                     const byCat = !this.cat || d.cat_id == this.cat;
                     return byTab && byCat;
                 });
             },
             count(t) {
                 if (t === 'active') return this.dishes.filter(d =>  d.available).length;
                 if (t === 'hidden') return this.dishes.filter(d => !d.available).length;
                 return this.dishes.length;
             },
         }">

        {{-- ── Page head ── --}}
        <div class="dash-head">
            <div>
                <div class="dash-eyebrow">
                    <span class="dash-line"></span>
                    {{ __('menu_owner.dashboard.eyebrow') }}
                </div>
                <h1 class="dash-h1">
                    @if($restaurant)
                        <span class="it">{{ $restaurant->name }}</span>
                    @else
                        {{ __('menu_owner.dashboard.title') }}
                    @endif
                </h1>
                @if($restaurant)
                    <p class="dash-subhead">
                        {{ $totalDishCount }} {{ Str::plural('dish', $totalDishCount) }}
                        across {{ $categories->count() }} {{ Str::plural('category', $categories->count()) }}
                        @if($totalViews > 0)
                            · {{ number_format($totalViews) }} total views
                        @endif
                    </p>
                @endif
            </div>
            <div class="dash-actions">
                @if($restaurant)
                    <a href="{{ route('public.menu', $restaurant->slug) }}"
                       target="_blank"
                       class="dash-btn dash-btn-ghost">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        {{ __('menu_owner.dashboard.preview') }}
                    </a>
                @endif
                <a href="{{ route('menu-owner.dishes.create') }}" class="dash-btn dash-btn-ink">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    {{ __('menu_owner.dashboard.add_dish') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- ── KPI strip ── --}}
        <div class="dash-kpis">
            <div class="dash-kpi">
                <div class="dk-label">{{ __('menu_owner.dashboard.active_dishes') }}</div>
                <div class="dk-value">
                    {{ $activeDishCount }}<span class="u">/{{ $totalDishCount }}</span>
                </div>
                <div class="dk-sub {{ $totalDishCount === 0 || $activeDishCount === $totalDishCount ? 'muted' : '' }}">
                    @if($totalDishCount === 0)
                        {{ __('menu_owner.dashboard.no_dishes_yet') }}
                    @elseif($activeDishCount === $totalDishCount)
                        {{ __('menu_owner.dashboard.all_dishes_active') }}
                    @else
                        {{ __('menu_owner.dashboard.hidden_count', ['count' => $totalDishCount - $activeDishCount]) }}
                    @endif
                </div>
            </div>
            <div class="dash-kpi">
                <div class="dk-label">{{ __('menu_owner.dashboard.total_views') }}</div>
                <div class="dk-value">{{ number_format($totalViews) }}</div>
                <div class="dk-sub muted">{{ __('menu_owner.dashboard.menu_page_visits') }}</div>
            </div>
            <div class="dash-kpi">
                <div class="dk-label">{{ __('menu_owner.dashboard.unique_visitors') }}</div>
                <div class="dk-value">{{ number_format($uniqueVisitors) }}</div>
                <div class="dk-sub muted">{{ __('menu_owner.dashboard.distinct_sessions') }}</div>
            </div>
            <div class="dash-kpi">
                <div class="dk-label">{{ __('menu_owner.dashboard.categories') }}</div>
                <div class="dk-value">{{ $categories->count() }}</div>
                <div class="dk-sub muted">
                    {{ $categories->sum('dishes_count') }} {{ Str::plural('dish', $categories->sum('dishes_count')) }} total
                </div>
            </div>
        </div>

        {{-- ── Toolbar ── --}}
        <div class="dash-toolbar">
            <div class="dash-tabs">
                <button class="dash-tab" :class="tab === 'all' ? 'on' : ''" @click="tab = 'all'">
                    {{ __('menu_owner.dashboard.filter_all') }} <span class="cnt" x-text="dishes.length"></span>
                </button>
                <button class="dash-tab" :class="tab === 'active' ? 'on' : ''" @click="tab = 'active'">
                    {{ __('menu_owner.dashboard.filter_active') }} <span class="cnt" x-text="count('active')"></span>
                </button>
                <button class="dash-tab" :class="tab === 'hidden' ? 'on' : ''" @click="tab = 'hidden'">
                    {{ __('menu_owner.dashboard.filter_hidden') }} <span class="cnt" x-text="count('hidden')"></span>
                </button>
            </div>
        </div>

        {{-- ── Two-column layout ── --}}
        <div class="dash-layout">

            {{-- Left: dish panel --}}
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <h3>
                        <span class="it">All</span> dishes
                        <span style="color:var(--muted);font-size:12px;font-weight:400">
                            · <span x-text="filtered.length"></span> items
                        </span>
                    </h3>
                </div>

                {{-- Category filter pills --}}
                @if($categories->isNotEmpty())
                    <div class="dash-cats">
                        <button class="dash-cat-pill"
                                :class="!cat ? 'on' : ''"
                                @click="cat = null">
                            All <span class="n">{{ $totalDishCount }}</span>
                        </button>
                        @foreach($categories as $category)
                            <button class="dash-cat-pill"
                                    :class="cat == {{ $category->id }} ? 'on' : ''"
                                    @click="cat = cat == {{ $category->id }} ? null : {{ $category->id }}">
                                {{ $category->name }}
                                <span class="n">{{ $category->dishes_count }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Dish table --}}
                <div class="dash-table-wrap">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th style="width:52px"></th>
                                <th>{{ __('menu_owner.dashboard.col_dish') }}</th>
                                <th>{{ __('menu_owner.dashboard.col_category') }}</th>
                                <th>{{ __('menu_owner.dashboard.col_price') }}</th>
                                <th>{{ __('menu_owner.dashboard.col_status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="d in filtered" :key="d.id">
                                <tr>
                                    <td>
                                        <div class="dish-thumb">
                                            <template x-if="d.image">
                                                <img :src="d.image" :alt="d.name" loading="lazy"
                                                     style="width:100%;height:100%;object-fit:cover">
                                            </template>
                                            <template x-if="!d.image">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                                    <path d="M8 3v8a2 2 0 002 2h0a2 2 0 002-2V3M10 13v8M16 3c-1 0-2 1-2 3v6h2v9"/>
                                                </svg>
                                            </template>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="dish-name" x-text="d.name"></span>
                                    </td>
                                    <td>
                                        <span style="font-size:12.5px;color:var(--muted)"
                                              x-text="d.cat ?? '—'"></span>
                                    </td>
                                    <td>
                                        <span class="dish-price" x-text="d.price"></span>
                                    </td>
                                    <td>
                                        <span class="dp-pill"
                                              :class="d.available ? 'live' : 'hidden-pill'">
                                            <span class="dot"></span>
                                            <span x-text="d.available ? '{{ __('menu_owner.dashboard.status_active') }}' : '{{ __('menu_owner.dashboard.status_hidden') }}'"></span>
                                        </span>
                                    </td>
                                    <td style="text-align:right">
                                        <a :href="d.edit_url" class="dp-row-btn" title="Edit">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 21v-4l11-11 4 4-11 11H3z"/><path d="M14 6l4 4"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filtered.length === 0">
                                <tr>
                                    <td colspan="6" class="dash-empty">
                                        {{ __('menu_owner.dashboard.no_match') }}
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="dash-panel-foot">
                    <span x-text="'{{ __('menu_owner.dashboard.showing_of') }}'.replace(':shown', filtered.length).replace(':total', dishes.length)"></span>
                    <a href="{{ route('menu-owner.dishes.index') }}">{{ __('menu_owner.dashboard.manage_all') }} →</a>
                </div>
            </div>

            {{-- Right column --}}
            <div class="dash-right-col">

                {{-- Phone preview --}}
                @if($restaurant)
                    <div class="dash-preview">
                        <div class="dash-preview-head">
                            <div class="plbl">
                                <span class="dot"></span>
                                {{ __('menu_owner.dashboard.live_preview') }}
                            </div>
                            <a href="{{ route('public.menu', $restaurant->slug) }}"
                               target="_blank"
                               class="plink">
                                {{ __('menu_owner.dashboard.open') }}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                                </svg>
                            </a>
                        </div>
                        <div class="dash-preview-stage">
                            <div class="dash-phone">
                                <div class="dash-phone-screen">
                                    <div class="ps-notch"></div>
                                    <div class="ps-status-bar">
                                        <span>9:41</span>
                                        <span style="opacity:.5">●●●</span>
                                    </div>
                                    <div class="ps-rest-head">
                                        <div class="ps-rest-name">{{ $restaurant->name }}</div>
                                        <div class="ps-rest-meta">
                                            Menu · {{ $activeDishCount }} {{ Str::plural('dish', $activeDishCount) }}
                                        </div>
                                    </div>
                                    <div class="ps-dish-list">
                                        @forelse($dishes->where('is_available', true)->take(4) as $dish)
                                            <div class="ps-dish">
                                                <div>
                                                    <div class="pn">{{ $dish->name }}</div>
                                                    @if($dish->description)
                                                        <div class="pd">{{ Str::limit($dish->description, 38) }}</div>
                                                    @endif
                                                </div>
                                                <div class="pp">{{ number_format((float) $dish->price, 2) }}</div>
                                            </div>
                                        @empty
                                            <div class="ps-empty">{{ __('menu_owner.dashboard.no_active_dishes') }}</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Stats mini cards --}}
                <div class="dash-mini-cards">
                    <div class="dash-mini-card">
                        <div class="mc-lbl">{{ __('menu_owner.dashboard.total_views') }}</div>
                        <div class="mc-val"><span class="it">{{ number_format($totalViews) }}</span></div>
                        <div class="mc-desc">{{ __('menu_owner.dashboard.menu_page_visits') }}</div>
                    </div>
                    <div class="dash-mini-card">
                        <div class="mc-lbl">{{ __('menu_owner.dashboard.visitors') }}</div>
                        <div class="mc-val"><span class="it">{{ number_format($uniqueVisitors) }}</span></div>
                        <div class="mc-desc">{{ __('menu_owner.dashboard.unique_sessions') }}</div>
                    </div>
                </div>

                {{-- Quick links --}}
                <div class="dash-quick">
                    <div class="dash-quick-head">
                        {{ __('menu_owner.dashboard.quick_actions') }}
                    </div>
                    <div class="dash-quick-list">
                        <a href="{{ route('menu-owner.dishes.create') }}" class="dash-quick-item">
                            <span class="qi-ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                            </span>
                            <span class="qi-label">{{ __('menu_owner.dashboard.add_dish_label') }}</span>
                            <span class="qi-arr">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                        <a href="{{ route('menu-owner.categories.index') }}" class="dash-quick-item">
                            <span class="qi-ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 17V7a4 4 0 014-4z"/>
                                </svg>
                            </span>
                            <span class="qi-label">{{ __('menu_owner.dashboard.manage_categories') }}</span>
                            <span class="qi-arr">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                        <a href="{{ route('menu-owner.statistics.index') }}" class="dash-quick-item">
                            <span class="qi-ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18"/><path d="M7 14l3-4 4 3 5-7"/>
                                </svg>
                            </span>
                            <span class="qi-label">{{ __('menu_owner.dashboard.view_statistics') }}</span>
                            <span class="qi-arr">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                        <a href="{{ route('menu-owner.restaurant.index') }}" class="dash-quick-item">
                            <span class="qi-ico">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 01-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.9.4l-.1.1a2 2 0 11-2.8-2.9l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 012.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z"/>
                                </svg>
                            </span>
                            <span class="qi-label">{{ __('menu_owner.dashboard.restaurant_settings') }}</span>
                            <span class="qi-arr">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path d="M9 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</x-sidebar-layout>

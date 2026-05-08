<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ $restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.social_links') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.social_links.title') }}</h1>
            @if ($restaurant && ! $restaurant->hasReachedSocialLinkLimit())
                <x-btn href="{{ route('menu-owner.social-links.create') }}" variant="primary" size="sm" class="gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('menu_owner.social_links.add_social_link') }}
                </x-btn>
            @endif
        </div>


        @if (! $restaurant)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-6">
                {{ __('menu_owner.social_links.create_menu_first') }}
                <a href="{{ route('menu-owner.restaurant.index') }}" class="underline ms-2">{{ __('menu_owner.common.go_to_menu') }}</a>
            </div>
        @elseif($socialLinks->isEmpty())
            <x-ui.empty
                title="{{ __('menu_owner.social_links.no_social_links') }}"
                description="{{ __('menu_owner.social_links.get_started') }}"
                icon='<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>'>
                <x-btn href="{{ route('menu-owner.social-links.create') }}" variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('menu_owner.social_links.add_social_link') }}
                </x-btn>
            </x-ui.empty>
        @else
            @if ($restaurant)
                <p style="font-size:13px;color:var(--muted);margin-bottom:20px">
                    {{ __('menu_owner.social_links.limit_text', ['count' => $socialLinks->count(), 'limit' => $restaurant->social_link_limit]) }}
                    @if ($restaurant->hasReachedSocialLinkLimit())
                        <span style="color:var(--danger);font-weight:600;margin-left:8px">{{ __('menu_owner.common.limit_reached') }}</span>
                    @else
                        <span style="color:var(--olive-deep);margin-left:8px">{{ __('menu_owner.common.remaining', ['count' => $restaurant->getRemainingSocialLinkSlots()]) }}</span>
                    @endif
                </p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($socialLinks as $socialLink)
                    @php
                        $platformColors = ['instagram' => 'bg-gradient-to-br from-pink-500 to-purple-600', 'x' => 'bg-sky-500', 'facebook' => 'bg-blue-600', 'tiktok' => 'bg-gray-900'];
                        $platformNames  = ['instagram' => 'Instagram', 'x' => 'X (Twitter)', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'];
                        $color = $platformColors[$socialLink->platform] ?? 'bg-gray-500';
                        $name  = $platformNames[$socialLink->platform] ?? ucfirst($socialLink->platform);
                    @endphp
                    <div class="qp-card">
                        <div class="qp-card-body">
                            <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
                                <div class="{{ $color }} w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0">
                                    @if ($socialLink->platform === 'instagram')
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                                    @elseif ($socialLink->platform === 'x')
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                    @elseif ($socialLink->platform === 'facebook')
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                                    @elseif ($socialLink->platform === 'tiktok')
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/></svg>
                                    @endif
                                </div>
                                <h3 style="font-size:15px;font-weight:600;color:var(--ink);margin:0">{{ $name }}</h3>
                            </div>
                            <p style="font-size:12.5px;color:var(--muted);word-break:break-all;margin:0">{{ $socialLink->url }}</p>
                        </div>
                        <div class="qp-card-foot">
                            <x-btn href="{{ route('menu-owner.social-links.edit', $socialLink) }}" variant="secondary" size="sm" class="flex-1">{{ __('menu_owner.common.edit') }}</x-btn>
                            <form id="del-sl-{{ $socialLink->id }}" action="{{ route('menu-owner.social-links.destroy', $socialLink) }}" method="POST" style="display:none">
                                @csrf @method('DELETE')
                            </form>
                            <x-btn type="button" variant="danger" size="sm"
                                @click="$dispatch('del-ask', { message: '{{ __('menu_owner.common.confirm_delete_social_link') }}', formId: 'del-sl-{{ $socialLink->id }}' })">
                                {{ __('menu_owner.common.delete') }}
                            </x-btn>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-sidebar-layout>

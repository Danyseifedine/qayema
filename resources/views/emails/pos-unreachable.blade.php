<x-mail::message>
# Your menu cannot reach your POS

{{ $restaurant->getTranslation('name', 'en', false) ?: $restaurant->slug }} has not heard from your POS since {{ ($link->last_synced_at ?? $link->created_at)->timezone($restaurant->localTimezone())->format('j M, H:i') }}. Until it answers, changes made in your POS do not reach your menu, and orders placed in your menu wait on your Orders page.

@if (filled($link->last_error))
**What went wrong last:** {{ $link->last_error }}
@endif

Check that your POS is online and that its key has not been revoked. Your menu keeps trying on its own, and Sync now on the Connect to POS card tries at once.

<x-mail::button :url="$dashboardUrl">
Open Connect to POS
</x-mail::button>

*Sent from Qayema.*
</x-mail::message>

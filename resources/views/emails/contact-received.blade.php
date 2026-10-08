@php
    $package = $contactMessage->package;
    $restaurant = $contactMessage->user?->restaurant;
    // The body is rendered as Markdown, so every character Markdown treats as
    // syntax is escaped with a backslash. The backslash itself goes first, or
    // it would escape the escapes added after it.
    $markdown = ['\\', '`', '*', '_', '[', ']', '(', ')', '#', '>', '!', '~'];
    $escaped = array_map(fn (string $character): string => '\\'.$character, $markdown);
    $safeMessage = str_replace($markdown, $escaped, $contactMessage->message);
@endphp
<x-mail::message>
# {{ $package ? 'Package request from ' . $contactMessage->name : 'New message from ' . $contactMessage->name }}

@if (filled($contactMessage->email))
**From:** {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;
@else
**From:** {{ $contactMessage->name }} (username {{ $contactMessage->user?->username }}, no email)
@endif
@if ($package)

**Requested package:** {{ $package->getTranslation('name', 'en') }}
@endif

---

{{ $safeMessage }}

---

@if ($restaurant)
<x-mail::button :url="\App\Filament\Admin\Resources\Restaurants\RestaurantResource::getUrl('edit', ['record' => $restaurant])">
Open {{ $restaurant->getTranslation('name', 'en', false) ?: $restaurant->slug }} in admin
</x-mail::button>
@endif

@if (filled($contactMessage->email))
<x-mail::button :url="'mailto:' . $contactMessage->email" color="{{ $restaurant ? 'success' : 'primary' }}">
Reply to {{ $contactMessage->name }}
</x-mail::button>
@endif

*Sent from Qayema.*
</x-mail::message>

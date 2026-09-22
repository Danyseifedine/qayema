@php
    $package = $contactMessage->package;
    $restaurant = $contactMessage->user?->restaurant;
    $safeMessage = str_replace(['\', '`', '*', '_', '[', ']', '(', ')', '#', '>', '!', '~'], ['\\', '\`', '\*', '\_', '\[', '\]', '\(', '\)', '\#', '\>', '\!', '\~'], $contactMessage->message);
@endphp
<x-mail::message>
# {{ $package ? 'Package request from ' . $contactMessage->name : 'New message from ' . $contactMessage->name }}

**From:** {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;
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

<x-mail::button :url="'mailto:' . $contactMessage->email" color="{{ $restaurant ? 'success' : 'primary' }}">
Reply to {{ $contactMessage->name }}
</x-mail::button>

*Sent from Qayema.*
</x-mail::message>

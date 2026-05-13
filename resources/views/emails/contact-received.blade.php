<x-mail::message>
# New message from {{ $contactMessage->name }}

**From:** {{ $contactMessage->name }} &lt;{{ $contactMessage->email }}&gt;

---

{{ $contactMessage->message }}

---

<x-mail::button :url="'mailto:' . $contactMessage->email">
Reply to {{ $contactMessage->name }}
</x-mail::button>

*Sent from the Qayema contact form.*
</x-mail::message>

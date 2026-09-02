<x-mail::message>
# Confirm your email subscription

Someone — we hope you — asked to join the mary.win email list with this address.

<x-mail::button :url="$confirmUrl">
Confirm subscription
</x-mail::button>

**What you agreed to:** {{ $consentText }}

The link expires in {{ config('mailing-list.confirmation_ttl_hours') }} hours.

If this was not you, ignore this message. Nothing will be sent to you unless the
button above is used.
</x-mail::message>

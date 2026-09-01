{{--
    One page per authentication state. `role="alert"` announces it on arrival —
    a redirect gives a screen-reader user no other cue that anything happened.
--}}
<x-layouts.auth.simple>
    <x-auth-header :title="$state->heading" :description="__('Sign in to mary.win')" />

    <p role="alert" data-outcome="{{ $state->outcome->value }}" class="text-sm text-zinc-600 dark:text-zinc-400">
        {{ $state->explanation }}
    </p>

    @if ($state->offersRetry())
        <flux:button variant="primary" class="w-full" :href="route('auth.redirect')">{{ __('Try again') }}</flux:button>
    @else
        <flux:button variant="ghost" class="w-full" :href="route('home')">{{ __('Back to mary.win') }}</flux:button>
    @endif
</x-layouts.auth.simple>

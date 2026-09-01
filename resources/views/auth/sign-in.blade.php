<x-layouts.auth.simple>
    <x-auth-header :title="__('Sign in')" :description="__('mary.win uses a shared account.')" />

    <flux:button variant="primary" class="w-full" :href="route('auth.redirect')">
        {{ __('Continue to sign in') }}
    </flux:button>
</x-layouts.auth.simple>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Your display name, and the account you signed in with')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        {{--
            Read-only on purpose. Both values belong to the shared account: the
            email is a claim refreshed on every sign-in, so an edit here would
            be reverted at the next login rather than doing anything.
        --}}
        <flux:separator variant="subtle" />

        <div class="my-6 space-y-4">
            <flux:heading size="sm">{{ __('Account') }}</flux:heading>

            <flux:text class="block">
                <span class="font-medium">{{ __('Email') }}</span><br>
                {{ $this->email() }}
            </flux:text>

            <flux:text class="block">
                <span class="font-medium">{{ __('Account ID') }}</span><br>
                <span class="font-mono text-xs">{{ $this->accountId() }}</span>
            </flux:text>

            <flux:text variant="subtle" class="text-sm">
                {{ __('Your email address and sign-in are managed by your shared account, not by this site.') }}
            </flux:text>
        </div>
    </x-settings.layout>
</section>

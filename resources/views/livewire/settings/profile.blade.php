<section>
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="wrs-form">
            <!-- Name -->
            <div class="wr-field">
                <label class="wr-field__label" for="name">{{ __('Name') }}</label>
                <input id="name" wire:model="name" type="text" class="wr-input" required autofocus autocomplete="name">
                @error('name') <p class="wr-error">{{ $message }}</p> @enderror
            </div>

            <!-- Email -->
            <div class="wr-field">
                <label class="wr-field__label" for="email">{{ __('Email') }}</label>
                <input id="email" wire:model="email" type="email" class="wr-input" required autocomplete="email">
                @error('email') <p class="wr-error">{{ $message }}</p> @enderror

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                    <p class="wr-hint" style="margin-top: 8px;">
                        {{ __('Your email address is unverified.') }}
                        <a href="#" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </a>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="wr-saved" style="margin-top: 6px;">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                @endif
            </div>

            <div class="wrs-actions">
                <button type="submit" class="wr-btn wr-btn--cta">{{ __('Save') }}</button>
                <x-action-message class="wr-saved" on="profile-updated">{{ __('Saved.') }}</x-action-message>
            </div>
        </form>

        <hr class="wrs-sep">

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>

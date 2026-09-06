<section>
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="wrs-form">
            <!-- Current password -->
            <div class="wr-field">
                <label class="wr-field__label" for="current_password">{{ __('Current password') }}</label>
                <input id="current_password" wire:model="current_password" type="password" class="wr-input" required
                    autocomplete="current-password">
                @error('current_password') <p class="wr-error">{{ $message }}</p> @enderror
            </div>

            <!-- New password -->
            <div class="wr-field">
                <label class="wr-field__label" for="password">{{ __('New password') }}</label>
                <input id="password" wire:model="password" type="password" class="wr-input" required
                    autocomplete="new-password">
                @error('password') <p class="wr-error">{{ $message }}</p> @enderror
            </div>

            <!-- Confirm password -->
            <div class="wr-field">
                <label class="wr-field__label" for="password_confirmation">{{ __('Confirm Password') }}</label>
                <input id="password_confirmation" wire:model="password_confirmation" type="password" class="wr-input"
                    required autocomplete="new-password">
                @error('password_confirmation') <p class="wr-error">{{ $message }}</p> @enderror
            </div>

            <div class="wrs-actions">
                <button type="submit" class="wr-btn wr-btn--cta">{{ __('Save') }}</button>
                <x-action-message class="wr-saved" on="password-updated">{{ __('Saved.') }}</x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>

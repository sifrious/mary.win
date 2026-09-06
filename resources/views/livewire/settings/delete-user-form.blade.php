<section class="wrs-danger" style="padding-left: 16px; margin-top: 4px;"
    x-data="{ confirming: {{ $errors->has('password') ? 'true' : 'false' }} }">
    <div style="margin-bottom: 12px;">
        <p class="wr-field__label" style="justify-content: flex-start; color: var(--pop);">{{ __('Delete account') }}</p>
        <p class="wr-hint" style="margin-top: 6px;">
            {{ __('Delete your account and all of its resources. This action cannot be undone.') }}
        </p>
    </div>

    <button type="button" class="wr-btn wr-btn--danger" x-show="!confirming" @click="confirming = true">
        {{ __('Delete account') }}
    </button>

    <div x-show="confirming" x-cloak>
        <p class="wr-hint" style="margin-bottom: 12px;">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm.') }}
        </p>
        <form wire:submit="deleteUser" class="wrs-form" style="max-width: 340px;">
            <div class="wr-field">
                <label class="wr-field__label" for="delete_password">{{ __('Password') }}</label>
                <input id="delete_password" wire:model="password" type="password" class="wr-input"
                    placeholder="{{ __('Confirm your password') }}">
                @error('password') <p class="wr-error">{{ $message }}</p> @enderror
            </div>
            <div class="wrs-actions">
                <button type="submit" class="wr-btn wr-btn--danger">{{ __('Delete account') }}</button>
                <button type="button" class="wr-btn wr-btn--quiet" @click="confirming = false">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</section>

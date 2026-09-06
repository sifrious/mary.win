<div class="wra">
    <div class="wra__head">
        <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>SECURE AREA</p>
        <h1 class="wra__title">Confirm <span class="wr-em">password.</span></h1>
        <p class="wra__lede">{{ __('This is a secure area of the application. Please confirm your password before continuing.') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="wr-status wra-status">{{ session('status') }}</div>
    @endif

    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">Confirm It's You</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body">
            <form wire:submit="confirmPassword" class="wra-form">
                <!-- Password -->
                <div class="wr-field">
                    <label class="wr-field__label" for="password">{{ __('Password') }}</label>
                    <div class="wr-input-wrap" x-data="{ show: false }">
                        <input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password"
                            class="wr-input" required autocomplete="current-password" placeholder="{{ __('Password') }}">
                        <button type="button" class="wr-reveal" @click="show = !show"
                            x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'">{{ __('Show') }}</button>
                    </div>
                    @error('password') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="wr-btn wr-btn--cta wr-btn--block">{{ __('Confirm') }} →</button>
            </form>
        </div>
    </div>
</div>

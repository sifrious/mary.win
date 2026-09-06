<div class="wra">
    <div class="wra__head">
        <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>NEW KEY</p>
        <h1 class="wra__title">Reset <span class="wr-em">password.</span></h1>
        <p class="wra__lede">{{ __('Please enter your new password below.') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="wr-status wra-status">{{ session('status') }}</div>
    @endif

    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">Choose A Password</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body">
            <form wire:submit="resetPassword" class="wra-form">
                <!-- Email Address -->
                <div class="wr-field">
                    <label class="wr-field__label" for="email">{{ __('Email') }}</label>
                    <input id="email" wire:model="email" type="email" class="wr-input" required autocomplete="email">
                    @error('email') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <!-- Password -->
                <div class="wr-field">
                    <label class="wr-field__label" for="password">{{ __('Password') }}</label>
                    <div class="wr-input-wrap" x-data="{ show: false }">
                        <input id="password" wire:model="password" :type="show ? 'text' : 'password'" type="password"
                            class="wr-input" required autocomplete="new-password" placeholder="{{ __('Password') }}">
                        <button type="button" class="wr-reveal" @click="show = !show"
                            x-text="show ? '{{ __('Hide') }}' : '{{ __('Show') }}'">{{ __('Show') }}</button>
                    </div>
                    @error('password') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <!-- Confirm Password -->
                <div class="wr-field">
                    <label class="wr-field__label" for="password_confirmation">{{ __('Confirm password') }}</label>
                    <input id="password_confirmation" wire:model="password_confirmation" type="password"
                        class="wr-input" required autocomplete="new-password"
                        placeholder="{{ __('Confirm password') }}">
                    @error('password_confirmation') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="wr-btn wr-btn--cta wr-btn--block">{{ __('Reset password') }} →</button>
            </form>
        </div>
    </div>
</div>

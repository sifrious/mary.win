<div class="wra">
    <div class="wra__head">
        <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>LOST THE KEY</p>
        <h1 class="wra__title">Forgot <span class="wr-em">password.</span></h1>
        <p class="wra__lede">{{ __('Enter your email to receive a password reset link.') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="wr-status wra-status">{{ session('status') }}</div>
    @endif

    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">Reset Link</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body">
            <form wire:submit="sendPasswordResetLink" class="wra-form">
                <!-- Email Address -->
                <div class="wr-field">
                    <label class="wr-field__label" for="email">{{ __('Email address') }}</label>
                    <input id="email" wire:model="email" type="email" class="wr-input" required autofocus
                        placeholder="email@example.com">
                    @error('email') <p class="wr-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="wr-btn wr-btn--cta wr-btn--block">{{ __('Email password reset link') }} →</button>
            </form>
        </div>
    </div>

    <p class="wra__foot">
        {{ __('Or, return to') }}
        <a href="{{ route('login') }}" wire:navigate>{{ __('log in') }}</a>
    </p>
</div>

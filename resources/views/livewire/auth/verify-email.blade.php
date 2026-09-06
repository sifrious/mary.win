<div class="wra">
    <div class="wra__head">
        <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>ONE MORE STEP</p>
        <h1 class="wra__title">Verify your <span class="wr-em">email.</span></h1>
        <p class="wra__lede">{{ __('Please verify your email address by clicking on the link we just emailed to you.') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="wr-status wra-status">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">Check Your Inbox</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body wra-form">
            <button type="button" wire:click="sendVerification" class="wr-btn wr-btn--cta wr-btn--block">
                {{ __('Resend verification email') }} →
            </button>

            <p class="wra__foot" style="margin-top: 4px;">
                <a href="#" wire:click.prevent="logout">{{ __('Log out') }}</a>
            </p>
        </div>
    </div>
</div>

@props(['heading' => '', 'subheading' => ''])

<div class="wrs">
    <aside class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">Sections</span>
            <span class="wr-plate__bar wr-plate__bar--sm" aria-hidden="true"></span>
        </div>
        <nav class="wrs__navlist">
            <a href="{{ route('settings.profile') }}" wire:navigate
                class="wrs__navitem {{ request()->routeIs('settings.profile') ? 'is-active' : '' }}">{{ __('Profile') }}</a>
            <a href="{{ route('settings.password') }}" wire:navigate
                class="wrs__navitem {{ request()->routeIs('settings.password') ? 'is-active' : '' }}">{{ __('Password') }}</a>
            <a href="{{ route('settings.appearance') }}" wire:navigate
                class="wrs__navitem {{ request()->routeIs('settings.appearance') ? 'is-active' : '' }}">{{ __('Appearance') }}</a>
        </nav>
    </aside>

    <div class="wr-plate">
        <div class="wr-plate__head">
            <span class="wr-label">{{ $heading }}</span>
            <span class="wr-plate__bar" aria-hidden="true"></span>
        </div>
        <div class="wr-plate__body" style="padding: 20px 18px;">
            @if ($subheading)
                <p class="wrs__sub">{{ $subheading }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>

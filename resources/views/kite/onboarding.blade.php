<x-layouts.kite title="Welcome">
    <section class="wr-hero">
        <div class="wr-hero__aurora" aria-hidden="true"></div>
        <div class="wr-hero__inner" style="padding-block: 40px 32px;">
            <p class="wr-entry"><span class="wr-dot" aria-hidden="true"></span>WELCOME TO KITE</p>
            <h1 class="wr-display wr-hero__title" style="font-size: var(--display-md);">You're <span class="wr-grad">in.</span></h1>
            <p class="wr-hero__lede">Kite is connected to your GitHub account. Pull in your repositories and pick
                the ones you'd like to study — then run analysis to build your code vocabulary.</p>
            <div class="wr-hero__actions">
                <a href="{{ route('kite.repositories.fetch-and-select') }}" class="wr-btn wr-btn--cta">Fetch &amp; select repositories →</a>
                <a href="{{ route('kite.dashboard') }}" class="wr-btn wr-btn--quiet">Skip to dashboard</a>
            </div>
        </div>
    </section>

    <div class="k-stats" style="margin-top: 40px;">
        <div class="k-stat"><p class="k-stat__label">Step 1</p><p class="k-stat__value--sm k-stat__value">Fetch repos</p></div>
        <div class="k-stat"><p class="k-stat__label">Step 2</p><p class="k-stat__value--sm k-stat__value">Analyze structure</p></div>
        <div class="k-stat"><p class="k-stat__label">Step 3</p><p class="k-stat__value--sm k-stat__value">Kite Read</p></div>
    </div>
</x-layouts.kite>

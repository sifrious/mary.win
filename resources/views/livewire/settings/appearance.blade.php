<section>
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Choose how mary.win looks on this device.')">
        <div x-data="{
            theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light',
            set(v) {
                this.theme = v;
                document.documentElement.dataset.theme = v;
                try { localStorage.setItem('winrar-theme', v); } catch (e) {}
            }
        }">
            <div class="wr-seg" role="group" aria-label="{{ __('Theme') }}">
                <button type="button" @click="set('light')" :class="theme === 'light' && 'is-active'">☀ {{ __('Light') }}</button>
                <button type="button" @click="set('dark')" :class="theme === 'dark' && 'is-active'">☾ {{ __('Dark') }}</button>
            </div>
            <p class="wr-hint" style="margin-top: 12px;">
                {{ __('Saved in this browser — it syncs with the lights toggle up in the header.') }}
            </p>
        </div>
    </x-settings.layout>
</section>

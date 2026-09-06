<x-layouts.kite title="Select Repositories">
    <div class="k-head">
        <div>
            <h1 class="k-head__title">Select your <span class="wr-em">repositories.</span></h1>
            <p class="k-head__sub">Choose which repositories to include in Kite Reader.</p>
        </div>
    </div>

    <x-kite.flash />

    @if ($repositories->count() > 0)
        <div class="wr-plate">
            <div class="wr-plate__head">
                <span class="wr-label">Available — {{ $repositories->count() }} repositories</span>
                <span class="wr-plate__bar" aria-hidden="true"></span>
            </div>
            <div class="wr-plate__body">
                <div class="k-actions" style="margin-bottom: 14px;">
                    <button type="button" class="wr-btn wr-btn--quiet wr-btn--sm" onclick="kiteSelect('all')">Select all</button>
                    <button type="button" class="wr-btn wr-btn--quiet wr-btn--sm" onclick="kiteSelect('none')">Select none</button>
                    <button type="button" class="wr-btn wr-btn--quiet wr-btn--sm" onclick="kiteSelect('public')">Public only</button>
                    <button type="button" class="wr-btn wr-btn--quiet wr-btn--sm" onclick="kiteSelect('private')">Private only</button>
                </div>

                <input type="text" id="repoSearch" class="k-search" placeholder="Search repositories by name…" onkeyup="kiteFilter()">
                <div class="k-search-stats" id="searchStats"></div>

                <form method="POST" action="{{ route('kite.repositories.update-selection') }}" style="margin-top: 14px;">
                    @csrf
                    <div class="k-list" style="max-height: 520px; border: 1px solid var(--line);">
                        @foreach ($repositories as $repo)
                            <label class="k-check" data-repo-item>
                                <input type="checkbox" name="repositories[]" value="{{ $repo->id }}"
                                    class="repo-checkbox" data-private="{{ $repo->private ? 'true' : 'false' }}"
                                    {{ $repo->is_active ? 'checked' : '' }}>
                                <div>
                                    <div class="k-check__name">
                                        <span data-repo-name>{{ $repo->name }}</span>
                                        <span class="k-badge {{ $repo->private ? 'k-badge--private' : 'k-badge--public' }}">
                                            {{ $repo->private ? 'Private' : 'Public' }}
                                        </span>
                                    </div>
                                    @if ($repo->description)
                                        <div class="k-check__desc">{{ Str::limit($repo->description, 140) }}</div>
                                    @endif
                                    <div class="k-check__meta" data-repo-full>{{ $repo->full_name }} · {{ $repo->default_branch }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="k-actions" style="margin-top: 18px;">
                        <button type="submit" class="wr-btn wr-btn--cta">Update selected repositories</button>
                        <a href="{{ route('kite.dashboard') }}" class="wr-btn wr-btn--quiet">Skip to dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="k-empty">
            <p class="k-empty__title">No repositories found</p>
            <p class="k-empty__text">Let's fetch your repositories from GitHub first.</p>
            <a href="{{ route('kite.repositories.fetch-and-select') }}" class="wr-btn wr-btn--cta">Fetch repositories from GitHub →</a>
        </div>
    @endif

    @push('scripts')
        <script>
            function kiteVisibleBoxes() {
                return Array.from(document.querySelectorAll('.repo-checkbox')).filter(function (cb) {
                    var item = cb.closest('[data-repo-item]');
                    return !item.style.display || item.style.display !== 'none';
                });
            }
            function kiteSelect(mode) {
                kiteVisibleBoxes().forEach(function (cb) {
                    if (mode === 'all') cb.checked = true;
                    else if (mode === 'none') cb.checked = false;
                    else if (mode === 'public') cb.checked = cb.dataset.private === 'false';
                    else if (mode === 'private') cb.checked = cb.dataset.private === 'true';
                });
                kiteStats();
            }
            function kiteFilter() {
                var q = (document.getElementById('repoSearch').value || '').toLowerCase();
                document.querySelectorAll('[data-repo-item]').forEach(function (item) {
                    var name = item.querySelector('[data-repo-name]').textContent.toLowerCase();
                    var full = item.querySelector('[data-repo-full]').textContent.toLowerCase();
                    item.style.display = (name.includes(q) || full.includes(q)) ? 'flex' : 'none';
                });
                kiteStats();
            }
            function kiteStats() {
                var visible = kiteVisibleBoxes();
                var selected = visible.filter(function (cb) { return cb.checked; }).length;
                document.getElementById('searchStats').textContent =
                    'Showing ' + visible.length + ' repositories · ' + selected + ' selected';
            }
            document.addEventListener('DOMContentLoaded', kiteStats);
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('repo-checkbox')) kiteStats();
            });
        </script>
    @endpush
</x-layouts.kite>

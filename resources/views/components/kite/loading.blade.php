{{-- Full-screen "Kite is reading" overlay. Exposes window.KiteLoading (show / hide /
     updateStatus / updateProgress / simulate) — the structure & files pages drive it. --}}
<div id="kite-loading-overlay" class="k-loading" style="display: none;">
    <div class="k-loading__box">
        <svg class="k-loading__kite" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M100 20 L160 80 L100 140 L40 80 Z" fill="var(--accent)" stroke="var(--accent-2)" stroke-width="3" />
            <line x1="100" y1="20" x2="100" y2="140" stroke="var(--plate)" stroke-width="3" />
            <line x1="40" y1="80" x2="160" y2="80" stroke="var(--plate)" stroke-width="3" />
        </svg>
        <h3 class="k-loading__title">Kite is reading your repository</h3>
        <p class="k-loading__status" id="kite-status-text">Initializing file analysis…</p>
        <div class="k-progress">
            <div id="kite-progress-fill" class="k-progress__fill"></div>
        </div>
        <div class="k-progress__count"><span id="kite-progress-count">0</span> files processed</div>
    </div>
</div>

<script>
    window.KiteLoading = {
        show() {
            document.getElementById('kite-loading-overlay').style.display = 'flex';
            this.updateStatus('Initializing file analysis…');
            this.updateProgress(0, 0);
        },
        hide() {
            document.getElementById('kite-loading-overlay').style.display = 'none';
        },
        updateStatus(message) {
            document.getElementById('kite-status-text').textContent = message;
        },
        updateProgress(processed, total) {
            const percentage = total > 0 ? (processed / total) * 100 : 0;
            document.getElementById('kite-progress-fill').style.width = percentage + '%';
            document.getElementById('kite-progress-count').textContent = processed;
        },
        simulate(totalFiles) {
            let processed = 0;
            const interval = setInterval(() => {
                processed++;
                this.updateProgress(processed, totalFiles);

                if (processed <= totalFiles * 0.3) {
                    this.updateStatus('Analyzing file structure…');
                } else if (processed <= totalFiles * 0.7) {
                    this.updateStatus('Reading file contents…');
                } else if (processed <= totalFiles * 0.9) {
                    this.updateStatus('Storing documents…');
                } else {
                    this.updateStatus('Finalizing analysis…');
                }

                if (processed >= totalFiles) {
                    clearInterval(interval);
                    setTimeout(() => {
                        this.updateStatus('Analysis complete! Reloading…');
                        setTimeout(() => {
                            this.hide();
                            window.location.reload();
                        }, 1000);
                    }, 500);
                }
            }, 200);
        }
    };
</script>

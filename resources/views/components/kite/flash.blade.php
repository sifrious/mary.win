@if (session('success'))
    <div class="k-alert k-alert--success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="k-alert k-alert--error">{{ session('error') }}</div>
@endif

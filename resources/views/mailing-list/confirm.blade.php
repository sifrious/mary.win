<x-layouts.plain :title="$heading">
    <h1 tabindex="-1" autofocus>{{ $heading }}</h1>

    <p>{{ $message }}</p>

    @if ($state === 'confirmed' && $unsubscribeUrl)
        <p class="muted">
            Change your mind at any time:
            <a class="break" href="{{ $unsubscribeUrl }}">unsubscribe</a>.
            This link keeps working — you may want to keep it.
        </p>
    @endif

    @if ($state === 'invalid')
        <p><a href="{{ route('home') }}#mailing-list">Return to the signup form</a>.</p>
    @else
        <p><a href="{{ route('home') }}">Return to {{ config('app.name') }}</a>.</p>
    @endif
</x-layouts.plain>

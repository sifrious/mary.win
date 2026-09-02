@php
    $heading = match ($state) {
        'confirm' => 'Unsubscribe from the email list?',
        'already' => 'You are already unsubscribed',
        'done' => 'You have been unsubscribed',
    };
@endphp

<x-layouts.plain :title="$heading">
    <h1 tabindex="-1" autofocus>{{ $heading }}</h1>

    @if ($state === 'confirm')
        {{-- A POST, so that link prefetching or a mail scanner opening the URL
             cannot unsubscribe somebody without their intent. --}}
        <p>This removes <strong class="break">{{ $subscription->email }}</strong> from the list. You can sign up again later.</p>

        <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('mailing-list.unsubscribe', ['subscription' => $subscription]) }}">
            @csrf
            <button type="submit">Yes, unsubscribe me</button>
        </form>
    @elseif ($state === 'already')
        <p><strong class="break">{{ $subscription->email }}</strong> is not on the list. Nothing has changed.</p>
    @else
        <p><strong class="break">{{ $subscription->email }}</strong> has been removed. We will not email you again unless you sign up once more.</p>
    @endif

    <p><a href="{{ route('home') }}">Return to {{ config('app.name') }}</a>.</p>
</x-layouts.plain>

<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Saved runs | Four Letter Words</title></head>
<body>
<main>
    <h1>Saved Four Letter Words runs</h1>
    <p><a href="{{ route('games.flw.play') }}">Back to current game</a></p>
    <p>The 50 most recently saved runs appear here. Opening a saved run replaces the current browser game, including any unsaved progress.</p>
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <ul>
        @forelse($runs as $run)
            <li><form method="post" action="{{ route('games.flw.resume', $run['run_id']) }}">@csrf
                <button type="submit">Open run saved {{ $run['updated_at'] }} UTC</button>
            </form></li>
        @empty
            <li>No saved runs yet.</li>
        @endforelse
    </ul>
</main>
</body>
</html>

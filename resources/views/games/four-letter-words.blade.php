<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Four Letter Words | mary.win</title>
    <style>
        body { background: #edf8f8; color: #203e3e; font: 1.1rem/1.6 system-ui, sans-serif; max-width: 40rem; margin: auto; padding: 1.5rem; }
        input, button { font: inherit; padding: .6rem; } a { color: #245574; }
        :focus-visible { outline: 3px solid #86456f; outline-offset: 3px; }
        input { display: block; max-width: 100%; box-sizing: border-box; } form { margin: 1rem 0; }
    </style>
</head>
<body>
<nav aria-label="Game navigation"><a href="{{ route('home') }}">mary.win</a> · <a href="{{ route('games.four-letter-words') }}">Tile view</a></nav>
<main>
    <h1>Four Letter Words</h1>
    <p>Start with a four-letter word. Then change one letter at a time without repeating a word.</p>
    @if(session('game_notice'))<p role="status">{{ session('game_notice') }}</p>@endif
    @if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <p>Streak: {{ $result['streak'] }}</p>
    @if($result['status'] === 'lost')
        <h2>Game over</h2>
        <p>{{ match($result['loss_reason']) { 'not_a_word' => 'That word is not in the dictionary.', 'repeat' => 'That word was already played.', default => 'Change exactly one letter.' } }}</p>
    @else
        <form method="post" action="{{ route('games.flw.submit') }}">
            @csrf
            <label for="word">{{ $result['streak'] ? 'Change one letter' : 'Enter a word with four letters' }}</label>
            <input id="word" name="word" type="text" minlength="4" maxlength="4" pattern="[A-Za-z]{4}" required autocomplete="off" autocapitalize="characters" spellcheck="false" value="{{ old('word', end($result['accepted_words']) ?: '') }}">
            <button type="submit">Submit word</button>
        </form>
    @endif
    @if($result['accepted_words'])<h2>Words played</h2><ol>@foreach($result['accepted_words'] as $word)<li>{{ $word }}</li>@endforeach</ol>@endif
    <form method="post" action="{{ route('games.flw.restart') }}">@csrf<button type="submit">Start a new game</button></form>
    <section aria-labelledby="save-heading">
        <h2 id="save-heading">Save your run</h2>
        @if($connected)
            <p><a href="{{ route('games.flw.saved') }}">View saved runs</a></p>
            <p>Save attaches this run to the account signed in at mary.is.</p>
            <form method="post" action="{{ route('games.flw.save') }}">@csrf<button type="submit">Save this run to my account</button></form>
            <form method="post" action="{{ route('games.flw.logout') }}">@csrf<button type="submit">Sign out of game saves</button></form>
        @elseif(config('games.client_id'))
            <a href="{{ route('games.flw.login') }}">Sign in with mary.is to save</a>
        @else
            <p>Guest play is available. Account saves are awaiting sign-in configuration.</p>
        @endif
    </section>
</main>
</body>
</html>

<?php

namespace App\Http\Controllers\Games;

use App\Games\Accounts\BrowserAccount;
use App\Games\FourLetterWords\Release;
use App\Games\FourLetterWords\RunConflict;
use App\Games\FourLetterWords\RunStore;
use App\Games\FourLetterWords\ValidateRun;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class BrowserGameController extends Controller
{
    public function show(Request $request, ValidateRun $validator)
    {
        $run = $request->session()->get('flw_browser_run', ['id' => (string) Str::uuid(), 'submissions' => [], 'owner' => null]);
        $request->session()->put('flw_browser_run', $run);

        return response()->view('games.four-letter-words', ['run' => $run, 'result' => $validator->validate($run['submissions']), 'connected' => $request->session()->has('game_credentials')]);
    }

    public function submit(Request $request, ValidateRun $validator)
    {
        $data = $request->validate(['word' => ['required', 'string', 'max:32']]);
        $run = $request->session()->get('flw_browser_run');
        abort_unless(is_array($run), 409, 'Open the game before submitting a word.');
        try {
            $words = [...$run['submissions'], $data['word']];
            $validator->validate($words);
        } catch (InvalidArgumentException) {
            return back()->withErrors(['word' => 'Enter four letters. A finished run needs a new game.']);
        }
        $run['submissions'] = array_map(fn (string $word) => strtoupper(trim($word)), $words);
        $request->session()->put('flw_browser_run', $run);

        return redirect()->route('games.flw.play');
    }

    public function restart(Request $request)
    {
        $request->session()->forget('flw_browser_run');

        return redirect()->route('games.flw.play');
    }

    public function save(Request $request, BrowserAccount $accounts, RunStore $store)
    {
        $run = $request->session()->get('flw_browser_run');
        abort_unless(is_array($run), 409);
        try {
            $account = $accounts->account($request->session());
            if ($account === null) {
                return redirect()->route('games.flw.play')->withErrors(['account' => 'Sign in before saving.']);
            }
            if ($run['owner'] !== null && $run['owner'] !== $account->id) {
                return back()->withErrors(['account' => 'This run belongs to another account. Start a new game for this account.']);
            }
            $store->save($account->id, $run['id'], $run['submissions']);
        } catch (RunConflict) {
            return back()->withErrors(['account' => 'The saved run has different progress. Start a new game.']);
        } catch (Throwable) {
            return back()->withErrors(['account' => 'Saving is unavailable. Your current run is still here. Try again or sign in again.']);
        }
        $run['owner'] = $account->id;
        $request->session()->put('flw_browser_run', $run);

        return back()->with('game_notice', 'Run saved to your account.');
    }

    public function saved(Request $request, BrowserAccount $accounts, RunStore $store)
    {
        try {
            $account = $accounts->account($request->session());
        } catch (Throwable) {
            return redirect()->route('games.flw.play')->withErrors(['account' => 'Saved runs are unavailable. Try again or sign in again.']);
        }
        if ($account === null) {
            return redirect()->route('games.flw.play')->withErrors(['account' => 'Sign in to view saved runs.']);
        }

        return response()->view('games.saved-runs', ['runs' => $store->recent($account->id)]);
    }

    public function resume(Request $request, string $run, BrowserAccount $accounts, RunStore $store)
    {
        try {
            $account = $accounts->account($request->session());
        } catch (Throwable) {
            return back()->withErrors(['account' => 'Account verification failed. Try signing in again.']);
        }
        abort_if($account === null, 401);
        $saved = $store->find($account->id, $run);
        abort_if($saved === null, 404);
        $release = Release::metadata();
        if ($saved['rules_version'] !== $release['rules_version'] || $saved['dictionary_version'] !== $release['dictionary_version']) {
            return back()->withErrors(['account' => 'This run uses an older game version and cannot resume here.']);
        }
        $request->session()->put('flw_browser_run', ['id' => $saved['run_id'], 'submissions' => $saved['submissions'], 'owner' => $account->id]);

        return redirect()->route('games.flw.play');
    }

    public function login(Request $request, BrowserAccount $accounts)
    {
        return redirect()->away($accounts->begin($request->session()));
    }

    public function callback(Request $request, BrowserAccount $accounts)
    {
        try {
            $accounts->complete($request->session(), $request->fullUrl());
        } catch (Throwable) {
            return redirect()->route('games.flw.play')->withErrors(['account' => 'Sign-in did not finish. Try again.']);
        }

        return redirect()->route('games.flw.play')->with('game_notice', 'Signed in. Choose Save this run to attach the current run to your account.');
    }

    public function logout(Request $request, BrowserAccount $accounts)
    {
        $accounts->logout($request->session());

        return redirect()->route('games.flw.play');
    }
}

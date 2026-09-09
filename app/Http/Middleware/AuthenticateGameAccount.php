<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Sifrious\AccountsClient\AccountTokenVerifier;
use UnexpectedValueException;

final class AuthenticateGameAccount
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (! is_string($token) || $token === '' || strlen($token) > 16384) {
            return response()->json(['message' => 'A game account access token is required.'], 401);
        }
        try {
            $account = (new AccountTokenVerifier(app(Factory::class), config('games.accounts_origin'), 'four-letter-words'))->verify($token);
        } catch (RequestException $exception) {
            $status = $exception->response->status();

            return response()->json(['message' => 'Account verification failed.'], in_array($status, [401, 403], true) ? $status : 503);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Account service is unavailable. Try again later.'], 503);
        } catch (UnexpectedValueException) {
            return response()->json(['message' => 'This token does not authorize this game.'], 403);
        }
        $request->attributes->set('game_account_id', $account->id);

        return $next($request);
    }
}

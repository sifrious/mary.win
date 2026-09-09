<?php

namespace App\Http\Controllers\Games;

use App\Games\FourLetterWords\Release;
use App\Games\FourLetterWords\RunConflict;
use App\Games\FourLetterWords\RunStore;
use App\Games\FourLetterWords\ValidateRun;
use App\Http\Controllers\Controller;
use App\Http\Requests\Games\FourLetterWordsRunRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class FourLetterWordsController extends Controller
{
    public function metadata(): JsonResponse
    {
        return response()->json(Release::metadata());
    }

    public function validateRun(FourLetterWordsRunRequest $request, ValidateRun $validator): JsonResponse
    {
        $data = $request->validated();

        try {
            return response()->json($validator->validate($data['submissions']));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['submissions' => $exception->getMessage()]);
        }
    }

    public function store(FourLetterWordsRunRequest $request, string $run, RunStore $store): JsonResponse
    {
        try {
            return response()->json($store->save($request->attributes->get('game_account_id'), $run, $request->validated('submissions')));
        } catch (RunConflict $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['submissions' => $exception->getMessage()]);
        }
    }

    public function show(Request $request, string $run, RunStore $store): JsonResponse
    {
        $saved = $store->find($request->attributes->get('game_account_id'), $run);
        abort_if($saved === null, 404);

        return response()->json($saved);
    }
}

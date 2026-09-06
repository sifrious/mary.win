<?php

namespace App\Http\Controllers\Games;

use App\Games\FourLetterWords\Release;
use App\Games\FourLetterWords\ValidateRun;
use App\Http\Controllers\Controller;
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

    public function validateRun(Request $request, ValidateRun $validator): JsonResponse
    {
        $data = $request->validate([
            'rules_version' => ['required', 'string', 'max:64'],
            'dictionary_version' => ['required', 'string', 'size:64'],
            'submissions' => ['present', 'array', 'list', 'max:5000'],
            'submissions.*' => ['required', 'string', 'max:32'],
        ]);
        $release = Release::metadata();

        if ($data['rules_version'] !== $release['rules_version'] || $data['dictionary_version'] !== $release['dictionary_version']) {
            return response()->json(['message' => 'Unsupported game version.', 'current' => $release], 409);
        }

        try {
            return response()->json($validator->validate($data['submissions']));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['submissions' => $exception->getMessage()]);
        }
    }
}

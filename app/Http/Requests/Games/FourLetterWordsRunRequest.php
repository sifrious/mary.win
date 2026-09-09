<?php

namespace App\Http\Requests\Games;

use App\Games\FourLetterWords\Release;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class FourLetterWordsRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rules_version' => ['required', 'string', 'max:64'],
            'dictionary_version' => ['required', 'string', 'size:64'],
            'submissions' => ['present', 'array', 'list', 'max:5000'],
            'submissions.*' => ['required', 'string', 'max:32'],
        ];
    }

    protected function passedValidation(): void
    {
        $release = Release::metadata();
        if ($this->validated('rules_version') !== $release['rules_version'] || $this->validated('dictionary_version') !== $release['dictionary_version']) {
            throw new HttpResponseException(response()->json(['message' => 'Unsupported game version.', 'current' => $release], 409));
        }
    }
}

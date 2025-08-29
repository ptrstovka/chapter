<?php

namespace App\Rules;

use App\Models\TemporaryUpload;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class TemporaryUploadRule implements ValidationRule
{
    public function __construct(
        protected string $scope
    ) {}

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            return;
        }

        if (is_string($value)) {
            if (
                TemporaryUpload::query()
                    ->where('uuid', $value)
                    ->where('scope', $this->scope)
                    ->whereBelongsTo(Auth::user())
                    ->exists()
            ) {
                return;
            }
        }

        $fail('This file is not valid anymore. Please, upload it again.')->translate();
    }

    /**
     * Create a new rule instance.
     */
    public static function scope(string $scope): static
    {
        return new static($scope);
    }
}

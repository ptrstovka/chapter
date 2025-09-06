<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class KeyValueRule implements ValidationRule
{
    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            return;
        }

        if (! is_array($value)) {
            $fail("The {$attribute} must be an array of key-value pairs.");
            return;
        }

        foreach ($value as $key => $val) {
            if (! preg_match('/^[a-z][a-z0-9_-]*$/i', (string) $key)) {
                $fail("The {$attribute} contains an invalid key: {$key}.");
            }

            if (! is_null($val) && ! is_string($val)) {
                $fail("The {$attribute}.{$key} must be a string or null.");
            }

            if (is_string($val) && strlen($val) > 256) {
                $fail("The {$attribute}.{$key} may not be greater than 256 characters.");
            }
        }
    }

    /**
     * Create a new Rule instance.
     */
    public static function make(): static
    {
        return new static;
    }
}

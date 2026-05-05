<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

class GivenValueShouldExists implements ValidationRule
{
    /**
     * @param  Closure(): mixed  $calculatedValue
     */
    public function __construct(
        protected Closure $calculatedValue,
        protected string $validationRule = 'validation.in',
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $result = ($this->calculatedValue)();
            throw_if(is_null($result) && filled($value));
        } catch (Throwable) {
            $fail($this->validationRule)->translate([
                'attribute' => $attribute,
            ]);
        }
    }
}

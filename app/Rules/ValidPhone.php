<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

final class ValidPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $toValidate = (string) $value;

        try {
            $phoneNumber = $phoneUtil->parse($toValidate, null);

            if (! $phoneUtil->isValidNumber($phoneNumber)) {
                $fail('The :attribute must be a valid international phone number.');
            }
        } catch (NumberParseException $e) {
            $fail('The :attribute must be a valid international phone number.');
        }
    }
}

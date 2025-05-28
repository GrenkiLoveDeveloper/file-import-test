<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DateValidator implements ValidationRule {
    public static function isValidDate(mixed $date, string $format): bool {

        if (empty($date) || ! is_string($date)) {
            return false;
        }

        $parsed = Carbon::createFromFormat($format, $date);

        return $parsed && $parsed->format($format) === $date;
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (! self::isValidDate($value, 'd.m.Y')) {
            $fail('Дата не валидна');
        }
    }
}

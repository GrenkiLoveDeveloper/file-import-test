<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Exception;

final class DateValidator {
    /**
     * Validate a date string against a specific format.
     *
     * @param mixed $date The date to validate
     * @param string $format The expected date format
     * @return bool True if the date is valid, false otherwise
     */
    public static function isValidDate(mixed $date, string $format): bool {

        if (empty($date) || ! is_string($date)) {
            return false;
        }

        try {
            $parsedDate = Carbon::createFromFormat($format, $date);

            return $parsedDate && $parsedDate->format($format) === $date;
        } catch (Exception $e) {
            return false;
        }
    }
}

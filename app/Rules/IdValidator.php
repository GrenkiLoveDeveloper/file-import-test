<?php

declare(strict_types=1);

namespace App\Rules;

final class IdValidator {
    /**
     * Validate an ID value.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isValid(mixed $value): bool {
        return ctype_digit($value) && (int) $value > 0;
    }
}

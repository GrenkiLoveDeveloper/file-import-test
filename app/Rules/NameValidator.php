<?php

declare(strict_types=1);

namespace App\Rules;

final class NameValidator {
    /**
     * Validate a name string.
     *
     * @param mixed $name
     * @return bool
     */
    public static function isValidName(mixed $name): bool {
        if (! is_string($name)) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z ]+$/', $name);
    }
}

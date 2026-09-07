<?php

namespace Rendane\FilamentJalali\Support;

use Hekmatinasser\Verta\Verta;

class JalaliFormatter
{
    public static function format(mixed $state, string $format = 'Y/m/d'): ?string
    {
        if (blank($state)) {
            return null;
        }

        if ($state instanceof Verta) {
            return $state->format($format);
        }

        return Verta::instance($state)->format($format);
    }
}

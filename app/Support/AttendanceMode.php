<?php

namespace App\Support;

final class AttendanceMode
{
    public const CHECKIN_ONLY = 'masuk_saja';

    public const CHECKIN_CHECKOUT = 'masuk_pulang';

    public static function normalize($value): string
    {
        $mode = strtolower(trim((string) ($value ?? '')));

        return $mode === self::CHECKIN_CHECKOUT
            ? self::CHECKIN_CHECKOUT
            : self::CHECKIN_ONLY;
    }
}

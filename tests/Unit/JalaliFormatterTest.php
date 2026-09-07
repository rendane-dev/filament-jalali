<?php

use Hekmatinasser\Verta\Verta;
use Rendane\FilamentJalali\Support\JalaliFormatter;

it('formats gregorian dates as jalali strings', function () {
    $gregorian = Verta::createJalaliDate(1404, 1, 1)->formatGregorian('Y-m-d');

    expect(JalaliFormatter::format($gregorian))->toBe('1404/01/01')
        ->and(JalaliFormatter::format($gregorian, 'Y-m-d'))->toBe('1404-01-01');
});

it('formats gregorian datetimes with custom jalali time format', function () {
    $gregorian = Verta::createJalaliDate(1404, 6, 15)->formatGregorian('Y-m-d') . ' 14:30:00';

    expect(JalaliFormatter::format($gregorian, 'Y/m/d H:i'))->toBe('1404/06/15 14:30');
});

it('returns null for blank jalali states', function () {
    expect(JalaliFormatter::format(null))->toBeNull()
        ->and(JalaliFormatter::format(''))->toBeNull();
});

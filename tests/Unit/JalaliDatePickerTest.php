<?php

use Hekmatinasser\Verta\Verta;

it('builds calendar day options with saturday-first padding using verta', function () {
    $firstDayOfMonth = Verta::createJalaliDate(1404, 1, 1);
    $options = [];

    for ($index = 0; $index < $firstDayOfMonth->dayOfWeek; $index++) {
        $options["pad_{$index}"] = ' ';
    }

    for ($day = 1; $day <= $firstDayOfMonth->daysInMonth; $day++) {
        $options[(string) $day] = (string) $day;
    }

    expect($options)->toHaveKey('pad_0')
        ->and($options)->toHaveKey('pad_5')
        ->and($options)->not->toHaveKey('pad_6')
        ->and($options)->toHaveKey('1')
        ->and($options['1'])->toBe('1');
});

it('converts jalali dates to gregorian storage format with verta', function () {
    $gregorian = Verta::createJalaliDate(1404, 1, 1)->formatGregorian('Y-m-d');

    expect($gregorian)->toBeString()
        ->and(Verta::instance($gregorian)->format('Y/m/d'))->toBe('1404/01/01');
});

it('provides twelve jalali month options from verta messages', function () {
    expect(Verta::getMessages('fa')['year_months'])->toHaveCount(12);
});

it('provides year options from 100 years ago to 10 years ahead by default', function () {
    $currentYear = Verta::now()->year;
    $options = [];

    for ($year = $currentYear - 100; $year <= $currentYear + 10; $year++) {
        $options[$year] = (string) $year;
    }

    expect($options)->toHaveCount(111)
        ->and(array_key_first($options))->toBe($currentYear - 100)
        ->and(array_key_last($options))->toBe($currentYear + 10);
});

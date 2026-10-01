# Filament Jalali

Jalali (Persian / Shamsi) dates for [Filament](https://filamentphp.com) v5.

The date picker shows a Jalali calendar and stores a Gregorian date, so it stays compatible with Laravel `date` and `datetime` columns. Table columns and a small formatter turn those stored values back into Jalali strings.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rendane/filament-jalali.svg?style=flat-square)](https://packagist.org/packages/rendane/filament-jalali)
[![Total Downloads](https://img.shields.io/packagist/dt/rendane/filament-jalali.svg?style=flat-square)](https://packagist.org/packages/rendane/filament-jalali)
[![License](https://img.shields.io/packagist/l/rendane/filament-jalali.svg?style=flat-square)](LICENSE)

## Features

- Jalali date picker for Filament forms, with Persian month names and a Saturday-first week
- Gregorian storage (`Y-m-d` by default) through Filament's datetime state cast
- Minimum and maximum dates, including disabled days in the calendar
- Today highlighted on the calendar
- Table column helpers for Jalali date, datetime, and time, including tooltips
- Dark mode styles for the calendar

## Requirements

- PHP 8.3 or higher
- Filament 5
- [`hekmatinasser/verta`](https://github.com/hekmatinasser/verta) 9

## Installation

```bash
composer require rendane/filament-jalali
```

The service provider is registered through Laravel package discovery and publishes the calendar stylesheet as a Filament asset.

If the calendar is unstyled after installation, publish Filament assets:

```bash
php artisan filament:assets
```

## Date picker

```php
use Rendane\FilamentJalali\Forms\Components\JalaliDatePicker;

JalaliDatePicker::make('birth_date')
    ->label('تاریخ تولد')
    ->required();
```

The field is read-only. Clicking it opens the calendar. Choosing a day writes the Gregorian date and closes the modal.

### Display and storage formats

`displayFormat()` controls the Jalali text shown in the input. `format()` is the Gregorian format passed to the state cast. Both default to a date without time.

```php
JalaliDatePicker::make('published_at')
    ->displayFormat('Y/m/d')
    ->format('Y-m-d');
```

Display formats use [Verta format characters](https://github.com/hekmatinasser/verta#format). `Y/m/d` renders as `1404/01/01`.

### Minimum and maximum dates

`minDate()` and `maxDate()` accept a `Carbon` instance, a date string, or a closure. Days outside the range are disabled, and the field adds `after_or_equal` / `before_or_equal` validation.

```php
JalaliDatePicker::make('due_date')
    ->minDate(now())
    ->maxDate(now()->addYear());
```

The year list runs from 100 years before the current Jalali year to 10 years after it. A minimum or maximum date replaces that end of the range with its own Jalali year.

## Table columns

`JalaliTextColumn` formats an existing Gregorian column. It does not change what is stored.

```php
use Rendane\FilamentJalali\Tables\Columns\JalaliTextColumn;

JalaliTextColumn::make('created_at')
    ->label('تاریخ')
    ->jalaliDate()
    ->jalaliDateTimeTooltip();
```

| Method | Default format |
| --- | --- |
| `jalaliDate()` | `Y/m/d` |
| `jalaliDateTime()` | `Y/m/d H:i` |
| `jalaliTime()` | `H:i` |
| `jalaliDateTooltip()` | `Y/m/d` |
| `jalaliDateTimeTooltip()` | `Y/m/d H:i` |
| `jalaliTimeTooltip()` | `H:i` |

Pass a format when you need a different pattern:

```php
JalaliTextColumn::make('created_at')
    ->jalaliDate('l j F Y');
```

## Formatting in PHP

`JalaliFormatter` accepts a `Verta` instance or any value `Verta::instance()` can parse, including Carbon dates and datetime strings. Blank values return `null`.

```php
use Rendane\FilamentJalali\Support\JalaliFormatter;

JalaliFormatter::format($record->created_at);                 // 1404/01/01
JalaliFormatter::format($record->created_at, 'Y/m/d H:i');    // 1404/01/01 14:30
```

## Storage

The picker stores a Gregorian date. Eloquent `date` and `datetime` casts keep working, and the Jalali value exists only in the interface and in formatted output.

## License

MIT. See [LICENSE](LICENSE).

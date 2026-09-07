<?php

namespace Rendane\FilamentJalali\Tables\Columns;

use Closure;
use Filament\Tables\Columns\TextColumn;
use Rendane\FilamentJalali\Support\JalaliFormatter;

class JalaliTextColumn extends TextColumn
{
    public function jalaliDate(string | Closure | null $format = null): static
    {
        $this->formatStateUsing(static function (JalaliTextColumn $column, mixed $state) use ($format): ?string {
            return JalaliFormatter::format(
                $state,
                $column->evaluate($format) ?? 'Y/m/d',
            );
        });

        return $this;
    }

    public function jalaliDateTime(string | Closure | null $format = null): static
    {
        $format ??= 'Y/m/d H:i';

        return $this->jalaliDate($format);
    }

    public function jalaliTime(string | Closure | null $format = null): static
    {
        $format ??= 'H:i';

        return $this->jalaliDate($format);
    }

    public function jalaliDateTooltip(string | Closure | null $format = null): static
    {
        $this->tooltip(static function (JalaliTextColumn $column, mixed $state) use ($format): ?string {
            return JalaliFormatter::format(
                $state,
                $column->evaluate($format) ?? 'Y/m/d',
            );
        });

        return $this;
    }

    public function jalaliDateTimeTooltip(string | Closure | null $format = null): static
    {
        $format ??= 'Y/m/d H:i';

        return $this->jalaliDateTooltip($format);
    }

    public function jalaliTimeTooltip(string | Closure | null $format = null): static
    {
        $format ??= 'H:i';

        return $this->jalaliDateTooltip($format);
    }
}

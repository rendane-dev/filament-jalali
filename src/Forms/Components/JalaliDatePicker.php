<?php

namespace Rendane\FilamentJalali\Forms\Components;

use Carbon\CarbonInterface;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\HasAffixes as HasAffixesContract;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Concerns\HasExtraAlpineAttributes;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Hekmatinasser\Verta\Verta;
use Livewire\Component;

class JalaliDatePicker extends Field implements HasAffixesContract, HasEmbeddedView
{
    use CanBeReadOnly;
    use HasAffixes;
    use HasExtraAlpineAttributes;
    use HasExtraInputAttributes;
    use HasPlaceholder;

    protected string | Closure | null $displayFormat = 'Y/m/d';

    protected string | Closure | null $format = 'Y-m-d';

    protected CarbonInterface | string | Closure | null $maxDate = null;

    protected CarbonInterface | string | Closure | null $minDate = null;

    protected ?Action $pickDateAction = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule('date');

        $this->readOnly();

        $this->suffixAction(
            fn (JalaliDatePicker $component): Action => $component->getPickDateAction(),
            isInline: true,
        );
    }

    public function getPickDateAction(): Action
    {
        return $this->pickDateAction ??= Action::make('pickDate')
            ->label('انتخاب تاریخ')
            ->icon(Heroicon::Calendar)
            ->modalHeading('')
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalWidth(Width::Medium)
            ->fillForm(function (JalaliDatePicker $component): array {
                $verta = $this->toVerta($component->getState()) ?? Verta::now();

                return [
                    'year' => $verta->year,
                    'month' => $verta->month,
                    'day' => $verta->day,
                ];
            })
            ->schema(fn (JalaliDatePicker $component): array => $component->getPickerSchema());
    }

    /**
     * @return array<int, mixed>
     */
    protected function getPickerSchema(): array
    {
        $picker = $this;

        return [
            Grid::make(2)
                ->schema([
                    Select::make('month')
                        ->label('ماه')
                        ->options($this->getMonthOptions())
                        ->selectablePlaceholder(false)
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            $this->resetDayIfInvalid($set, $get);
                        })
                        ->rules('required'),
                    Select::make('year')
                        ->label('سال')
                        ->options(fn (): array => $this->getYearOptions())
                        ->optionsLimit(fn (): int => count($this->getYearOptions()))
                        ->selectablePlaceholder(false)
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get): void {
                            $this->resetDayIfInvalid($set, $get);
                        })
                        ->rules('required'),
                ]),
            Grid::make(7)
                ->columnSpanFull()
                ->extraAttributes(['class' => 'fi-fo-jalali-calendar-weekdays'])
                ->schema(
                    collect($this->getWeekdayAbbreviations())
                        ->map(
                            fn (string $weekday): Text => Text::make($weekday)
                                ->weight('medium')
                                ->size('sm')
                                ->extraAttributes(['class' => 'text-center']),
                        )
                        ->all(),
                ),
            JalaliCalendarDayToggleButtons::make('day')
                ->hiddenLabel()
                ->live()
                ->options(fn (Get $get): array => $this->getCalendarDayOptions(
                    (int) $get('year'),
                    (int) $get('month'),
                ))
                ->todayDayUsing(fn (Get $get): ?int => $this->getTodayDayForViewingMonth(
                    (int) $get('year'),
                    (int) $get('month'),
                ))
                ->disableOptionWhen(function (string $value, Get $get): bool {
                    if ($this->isPaddingDay($value)) {
                        return true;
                    }

                    return $this->isDayDisabled(
                        (int) $get('year'),
                        (int) $get('month'),
                        (int) $value,
                    );
                })
                ->gridDirection(GridDirection::Row)
                ->columns(7)
                ->columnSpanFull()
                ->extraAttributes([
                    'class' => 'fi-fo-jalali-calendar-days',
                ])
                ->afterStateUpdated(function (?string $state, Get $get, Component $livewire) use ($picker): void {
                    if (blank($state) || $picker->isPaddingDay($state)) {
                        return;
                    }

                    if ($picker->isDayDisabled((int) $get('year'), (int) $get('month'), (int) $state)) {
                        return;
                    }

                    $picker->state(
                        Verta::createJalaliDate((int) $get('year'), (int) $get('month'), (int) $state)
                            ->formatGregorian('Y-m-d'),
                    );

                    $livewire->unmountAction();
                }),
        ];
    }

    protected function resetDayIfInvalid(Set $set, Get $get): void
    {
        $day = $get('day');

        if (blank($day) || $this->isPaddingDay((string) $day)) {
            return;
        }

        $monthStart = Verta::createJalaliDate((int) $get('year'), (int) $get('month'), 1);

        if ((int) $day > $monthStart->daysInMonth) {
            $set('day', null);
        }
    }

    protected function getTodayDayForViewingMonth(int $year, int $month): ?int
    {
        $today = Verta::now();

        if ($year !== $today->year || $month !== $today->month) {
            return null;
        }

        return $today->day;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(DateTimeStateCast::class, [
                'format' => $this->getFormat(),
                'internalFormat' => 'Y-m-d',
                'timezone' => config('app.timezone'),
            ]),
        ];
    }

    public function displayFormat(string | Closure | null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function format(string | Closure | null $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function maxDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->maxDate = $date;

        $this->rule(static function (JalaliDatePicker $component) {
            return "before_or_equal:{$component->getMaxDate()}";
        }, static fn (JalaliDatePicker $component): bool => (bool) $component->getMaxDate());

        return $this;
    }

    public function minDate(CarbonInterface | string | Closure | null $date): static
    {
        $this->minDate = $date;

        $this->rule(static function (JalaliDatePicker $component) {
            return "after_or_equal:{$component->getMinDate()}";
        }, static fn (JalaliDatePicker $component): bool => (bool) $component->getMinDate());

        return $this;
    }

    public function getDisplayFormat(): string
    {
        return $this->evaluate($this->displayFormat) ?? 'Y/m/d';
    }

    public function getFormat(): string
    {
        return $this->evaluate($this->format) ?? 'Y-m-d';
    }

    public function getMaxDate(): ?string
    {
        return $this->evaluate($this->maxDate);
    }

    public function getMinDate(): ?string
    {
        return $this->evaluate($this->minDate);
    }

    public function getMinJalaliYear(): ?int
    {
        if (blank($this->getMinDate())) {
            return null;
        }

        return $this->toVerta($this->getMinDate())?->year;
    }

    public function getMaxJalaliYear(): ?int
    {
        if (blank($this->getMaxDate())) {
            return null;
        }

        return $this->toVerta($this->getMaxDate())?->year;
    }

    public function getDisplayState(): ?string
    {
        return $this->toVerta($this->getState())?->format($this->getDisplayFormat());
    }

    public function toEmbeddedHtml(): string
    {
        $extraAlpineAttributes = $this->getExtraAlpineAttributes();
        $extraAttributeBag = $this->getExtraAttributeBag();
        $extraInputAttributeBag = $this->getExtraInputAttributeBag();
        $id = $this->getId();
        $isDisabled = $this->isDisabled();
        $isPrefixInline = $this->isPrefixInline();
        $isSuffixInline = $this->isSuffixInline();
        $prefixActions = $this->getPrefixActions();
        $prefixIcon = $this->getPrefixIcon();
        $prefixIconColor = $this->getPrefixIconColor();
        $prefixLabel = $this->getPrefixLabel();
        $suffixActions = $this->getSuffixActions();
        $suffixIcon = $this->getSuffixIcon();
        $suffixIconColor = $this->getSuffixIconColor();
        $suffixLabel = $this->getSuffixLabel();
        $statePath = $this->getStatePath();
        $placeholder = $this->getPlaceholder();
        $displayState = $this->getDisplayState();
        $pickDateAction = $this->getAction('pickDate');
        $wireClickHandler = ($pickDateAction !== null && ! $isDisabled)
            ? $pickDateAction->getLivewireClickHandler()
            : null;

        $wrapperAttributes = $extraAttributeBag
            ->merge([
                'x-on:focus-input.stop' => "\$el.querySelector('input:not([type=hidden])')?.focus()",
            ], escape: false)
            ->class(['fi-fo-jalali-date-picker']);

        $displayInputAttributes = $extraInputAttributeBag
            ->merge($extraAlpineAttributes, escape: false)
            ->merge([
                'autofocus' => $this->isAutofocused(),
                'disabled' => $isDisabled,
                'id' => $id,
                'placeholder' => filled($placeholder) ? e($placeholder) : null,
                'readonly' => true,
                'required' => $this->isRequired(),
                'type' => 'text',
                'value' => filled($displayState) ? e($displayState) : null,
                'wire:click' => $wireClickHandler,
            ], escape: false)
            ->class([
                'fi-input',
                'cursor-pointer' => filled($wireClickHandler),
                'fi-input-has-inline-prefix' => $isPrefixInline && (count($prefixActions) || $prefixIcon || filled($prefixLabel)),
                'fi-input-has-inline-suffix' => $isSuffixInline && (count($suffixActions) || $suffixIcon || filled($suffixLabel)),
            ]);

        ob_start(); ?>

        <input
            <?= (new FilamentComponentAttributeBag)
                ->merge([
                    'type' => 'hidden',
                    $this->applyStateBindingModifiers('wire:model') => $statePath,
                ], escape: false)
                ->toHtml() ?>
        />

        <input <?= $displayInputAttributes->toHtml() ?> />

        <?php $slotHtml = ob_get_clean();

        return $this->wrapEmbeddedHtml(
            $this->wrapInputHtml(
                $slotHtml,
                attributes: $wrapperAttributes,
            ),
            inlineLabelVerticalAlignment: VerticalAlignment::Center,
        );
    }

    protected function toVerta(mixed $date): ?Verta
    {
        if (blank($date)) {
            return null;
        }

        if ($date instanceof Verta) {
            return $date;
        }

        return Verta::instance($date);
    }

    /**
     * @return array<int, string>
     */
    protected function getMonthOptions(): array
    {
        return Verta::getMessages('fa')['year_months'];
    }

    /**
     * @return array<int, string>
     */
    protected function getYearOptions(): array
    {
        $currentYear = Verta::now()->year;
        $minYear = $this->getMinJalaliYear() ?? ($currentYear - 100);
        $maxYear = $this->getMaxJalaliYear() ?? ($currentYear + 10);

        $options = [];

        for ($year = $minYear; $year <= $maxYear; $year++) {
            $options[$year] = (string) $year;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    protected function getWeekdayAbbreviations(): array
    {
        return collect(Verta::getMessages('fa')['weekdays'])
            ->map(fn (string $weekday): string => mb_substr($weekday, 0, 1, 'UTF-8'))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function getCalendarDayOptions(int $year, int $month): array
    {
        $firstDayOfMonth = Verta::createJalaliDate($year, $month, 1);
        $options = [];

        for ($index = 0; $index < $firstDayOfMonth->dayOfWeek; $index++) {
            $options["pad_{$index}"] = ' ';
        }

        for ($day = 1; $day <= $firstDayOfMonth->daysInMonth; $day++) {
            $options[(string) $day] = (string) $day;
        }

        return $options;
    }

    protected function isPaddingDay(string $value): bool
    {
        return str_starts_with($value, 'pad_');
    }

    protected function isDayDisabled(int $year, int $month, int $day): bool
    {
        $selectedDate = Verta::createJalaliDate($year, $month, $day)->startDay();

        if (filled($this->getMinDate())) {
            $minimum = Verta::instance($this->getMinDate())->startDay();

            if ($selectedDate->lt($minimum)) {
                return true;
            }
        }

        if (filled($this->getMaxDate())) {
            $maximum = Verta::instance($this->getMaxDate())->startDay();

            if ($selectedDate->gt($maximum)) {
                return true;
            }
        }

        return false;
    }
}

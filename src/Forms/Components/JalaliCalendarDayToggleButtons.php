<?php

namespace Rendane\FilamentJalali\Forms\Components;

use Closure;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\Enums\GridDirection;
use Filament\Support\View\Components\ButtonComponent;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Js;

use function Filament\Support\generate_icon_html;

class JalaliCalendarDayToggleButtons extends ToggleButtons
{
    protected Closure | null $todayDayUsing = null;

    public function todayDayUsing(?Closure $callback): static
    {
        $this->todayDayUsing = $callback;

        return $this;
    }

    public function getTodayDay(): ?int
    {
        $todayDay = $this->evaluate($this->todayDayUsing);

        return is_int($todayDay) ? $todayDay : null;
    }

    protected function isTodayOption(string | int $value): bool
    {
        $todayDay = $this->getTodayDay();

        if ($todayDay === null || ! is_numeric($value)) {
            return false;
        }

        return (int) $value === $todayDay;
    }

    public function toEmbeddedHtml(): string
    {
        if ($this->isGrouped()) {
            return $this->toGroupedEmbeddedHtml();
        }

        $gridDirection = $this->getGridDirection() ?? GridDirection::Column;
        $id = $this->getId();
        $isDisabled = $this->isDisabled();
        $isInline = $this->isInline();
        $isMultiple = $this->isMultiple();
        $statePath = $this->getStatePath();
        $areButtonLabelsHidden = $this->areButtonLabelsHidden();
        $wireModelAttribute = $this->applyStateBindingModifiers('wire:model');
        $extraInputAttributeBag = $this->getExtraInputAttributeBag()->class(['fi-fo-toggle-buttons-input']);
        $isAutofocused = $this->isAutofocused();

        $containerAttributes = $this->getExtraAttributeBag();

        if (! $isInline) {
            $containerAttributes = $containerAttributes->grid($this->getColumns(), $gridDirection);
        }

        $containerAttributes = $containerAttributes
            ->merge([
                'aria-labelledby' => "{$id}-label",
                'role' => $isMultiple ? 'group' : 'radiogroup',
            ], escape: false)
            ->class([
                'fi-fo-toggle-buttons',
                'fi-inline' => $isInline,
            ]);

        $first = true;

        ob_start(); ?>

        <div <?= $containerAttributes->toHtml() ?>>
            <?php foreach ($this->getOptions() as $value => $label) { ?>
                <?php
                    $inputId = "{$id}-{$value}";
                $shouldOptionBeDisabled = $isDisabled || $this->isOptionDisabled($value, $label);
                $color = $this->getColor($value) ?? 'primary';
                $icon = $this->getIcon($value);
                $tooltip = $this->getTooltip($value);

                $buttonAttributes = (new FilamentComponentAttributeBag)
                    ->merge([
                        'aria-disabled' => $shouldOptionBeDisabled ? 'true' : null,
                        'aria-label' => $areButtonLabelsHidden ? e(trim(strip_tags((string) $label))) : null,
                        'disabled' => $shouldOptionBeDisabled && blank($tooltip),
                        'for' => e($inputId),
                    ], escape: false)
                    ->class([
                        'fi-btn',
                        'fi-size-md',
                        'fi-disabled' => $shouldOptionBeDisabled,
                        'fi-fo-jalali-today' => $this->isTodayOption($value),
                    ])
                    ->color(ButtonComponent::class, $color);
                ?>

                <div class="fi-fo-toggle-buttons-btn-ctn">
                    <input
                        <?php if ($first && $isAutofocused) { ?> autofocus <?php } ?>
                        <?php if ($shouldOptionBeDisabled) { ?> disabled <?php } ?>
                        id="<?= e($inputId) ?>"
                        <?php if (! $isMultiple) { ?>
                            name="<?= e($id) ?>"
                        <?php } ?>
                        type="<?= $isMultiple ? 'checkbox' : 'radio' ?>"
                        value="<?= e($value) ?>"
                        <?= $wireModelAttribute ?>="<?= e($statePath) ?>"
                        <?= $extraInputAttributeBag->toHtml() ?>
                    />

                    <label
                        <?php if (filled($tooltip)) { ?>
                            x-tooltip="{ content: <?= Js::from($tooltip) ?>, theme: $store.theme, allowHTML: <?= Js::from($tooltip instanceof Htmlable) ?> }"
                        <?php } ?>
                        <?= $buttonAttributes->toHtml() ?>
                    >
                        <?php if (filled($icon)) { ?>
                            <?= generate_icon_html($icon)?->toHtml() ?>
                        <?php } ?>

                        <?php if (! $areButtonLabelsHidden) { ?>
                            <?= e($label) ?>
                        <?php } ?>
                    </label>
                </div>
                <?php $first = false; ?>
            <?php } ?>
        </div>

        <?php return $this->wrapEmbeddedHtml(ob_get_clean(), extraWrapperAttributes: ['class' => 'fi-fo-toggle-buttons-wrp', 'tabindex' => '-1'], labelTag: 'div');
    }
}

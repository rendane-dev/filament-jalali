<?php

namespace Rendane\FilamentJalali\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentJalaliServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FilamentAsset::register([
            Css::make(
                'jalali-date-picker',
                __DIR__ . '/../../resources/css/jalali-date-picker.css',
            ),
        ]);
    }
}

<?php

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /*** Register services.
     */
    public function register(): void
    {
        //
    }

    /*** Bootstrap services.
     */
    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('custom-local-stylesheet', asset('css/filament.css')),
            Css::make('fontawesomepro-stylesheet', asset('css/fontawesomepro.min.css')),
        ]);

        FilamentAsset::register([
            Js::make('fontawesomepro-script', asset('js/fontawesomepro.min.js'))->loadedOnRequest(),
        ]);
    }
}

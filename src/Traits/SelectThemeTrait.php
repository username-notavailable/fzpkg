<?php

namespace Fuzzy\Fzpkg\Traits;

use Fuzzy\Fzpkg\FzpkgServiceProvider;

trait SelectThemeTrait
{
    public function selectTheme() : string
    {
        if (config('app.env') === 'production') {
            $themes = FzpkgServiceProvider::getEnabledThemes();
        }
        else {
            $themes = FzpkgServiceProvider::getAvailableThemes();
        }

        if (count($themes) === 1) {
            return $themes[0];
        }
        else {
            return $this->choice('Theme selection',
                $themes,
                0,
                3
            );
        }
    }
}
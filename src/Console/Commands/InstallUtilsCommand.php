<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Fuzzy\Fzpkg\FzpkgServiceProvider;

final class InstallUtilsCommand extends BaseCommand
{
    protected $signature = 'fz:install:utils { --theme= : Selected theme (if themes enabled) }';

    protected $description = 'Install utilities';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $this->checkEnvFlag('FZ_UTILS_INSTALLED', 'Fz utils already installed');

        $theme = '';

        if (!empty($this->option('theme'))) {
            if (!$filesystem->isDirectory(base_path('resources' . DIRECTORY_SEPARATOR . $this->option('theme')))) {
                $this->fail('Theme "' . $this->option('theme') . '" not exists');
            }
            else {
                $theme = $this->option('theme');
            }
        }

        if (config('fz.load.cookies.theme') && empty($theme)) {
            if (config('app.env') === 'production') {
                $themes = FzpkgServiceProvider::getEnabledThemes();
            }
            else {
                $themes = FzpkgServiceProvider::getAvailableThemes();
            }

            if (count($themes) === 1) {
                $theme = $themes[0];
            }
            else {
                $theme = $this->choice('Theme selection?',
                    $themes,
                    0,
                    3
                );
            }
        }

        if (empty($theme)) {
            $resourceSubPath = '';
        }
        else {
            $resourceSubPath = $theme . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        }

        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/favicons', public_path());

        $filesystem->ensureDirectoryExists(resource_path($resourceSubPath . 'js'));
        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/js', resource_path($resourceSubPath . 'js'));

        $filesystem->ensureDirectoryExists(resource_path($resourceSubPath . 'sass'));
        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/sass', resource_path($resourceSubPath . 'sass'));

        $viteConfigFile = base_path('vite-theme.config.js');

        if (!$filesystem->exists($viteConfigFile)) {
            $filesystem->copy(__DIR__.'/../../../data/utils/vite-theme.config.js', $viteConfigFile);
        }
        else {
            $this->outLabelledInfo('"vite-theme.config.js" already exists, skipped...');
        }
        
        /* --- */

        $this->updateNodePackages(function ($packages) {
            return [
                'sass' => '^1.77.8'
            ] + $packages;
        }, true);

        /* --- */

        $targets = [
            'FZ_UTILS_INSTALLED=' => [
                'from' => 'FZ_UTILS_INSTALLED=.*$',
                'to' => 'FZ_UTILS_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);

        if (empty($theme)) {
            $this->outLabelledSuccess('Fuzzy utils installed into main resources directory');
        }
        else {
            $this->outLabelledSuccess("Fuzzy utils installed for theme [$theme]");
        }
    }
}

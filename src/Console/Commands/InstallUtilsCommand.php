<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallUtilsCommand extends BaseCommand
{
    protected $signature = 'fz:install:utils';

    protected $description = 'Install utilities';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $this->checkEnvFlag('FZ_UTILS_INSTALLED', 'Fz utils already installed');

        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/favicons', public_path());

        $fileSystem->ensureDirectoryExists(resource_path('js'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/js', resource_path('js'));

        $fileSystem->ensureDirectoryExists(resource_path('sass'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/sass', resource_path('sass'));

        //$fileSystem->ensureDirectoryExists(resource_path('utils'));

        $laravelBootstrapJsPath = resource_path('js/bootstrap.js');

        if ($fileSystem->exists($laravelBootstrapJsPath)) {
            $fileSystem->append($laravelBootstrapJsPath, "
            
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import.meta.glob([
    '../assets/**',
]);

import utils from './utils';
window.utils = utils;
");
        }

        $viteFilePath = base_path('vite.config.js');

        if ($fileSystem->exists($viteFilePath)) {
            $data = $fileSystem->get($viteFilePath);

            if (mb_stripos($data, 'resources/css/app.css') !== false) {
                $data = preg_replace('@resources/css/app.css@', 'resources/sass/app.scss', $data);

                if (!is_null($data)) {
                    $fileSystem->put($viteFilePath, $data);
                }
            }
        }

        /* --- */

        $this->updateNodePackages(function ($packages) {
            return [
                'bootstrap' => '^5.3.3',
            ] + $packages;
        }, false);

        $this->updateNodePackages(function ($packages) {
            return [
                'sass' => '^1.77.8'
            ] + $packages;
        }, true);

        $this->runCommands(['npm install --include=dev', 'npm run build']);

        /* --- */

        $targets = [
            'APP_LOCALE=' => [
                'from' => 'APP_LOCALE=.*$',
                'to' => 'APP_LOCALE=it'
            ],
            'APP_FAKER_LOCALE=' => [
                'from' => 'APP_FAKER_LOCALE=.*$',
                'to' => 'APP_FAKER_LOCALE=it_IT'
            ],
            'APP_URL=' => [
                'from' => 'APP_URL=.*$',
                'to' => 'APP_URL=http://localhost:8000'
            ]
        ];

        $this->updateEnvFile($targets);

        /* --- */

        $targets = [
            'FZ_UTILS_INSTALLED=' => [
                'from' => 'FZ_UTILS_INSTALLED=.*$',
                'to' => 'FZ_UTILS_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);
    }
}

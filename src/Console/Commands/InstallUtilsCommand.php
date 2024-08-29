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

        $envFilePath = base_path('.env');

        if ($fileSystem->exists($envFilePath)) {
            $data = $fileSystem->get($envFilePath);

            if (mb_stripos($data, 'FZ_UTILS_INSTALLED') !== false) {
                $fzUtilsAlreadyInstalled = env('FZUTILS_INSTALLED');

                if (is_bool($fzUtilsAlreadyInstalled)) {
                    if ($fzUtilsAlreadyInstalled) {
                        $this->fail('Fz utils already installed');
                    }
                }
                else {
                    $this->fail('FZ_UTILS_INSTALLED into .env must be boolean');
                }
            }
        }
        else {
            $this->fail('.env file not found');
        }

        //$fileSystem->ensureDirectoryExists(resource_path('views/components/layouts'));
        //$fileSystem->copyDirectory(__DIR__.'/../../../data/utils/views', resource_path('views'));

        //$fileSystem->ensureDirectoryExists(resource_path('css'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/favicons', public_path());

        $fileSystem->ensureDirectoryExists(resource_path('js'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/js', resource_path('js'));

        $fileSystem->ensureDirectoryExists(resource_path('sass'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/utils/sass', resource_path('sass'));

        $fileSystem->ensureDirectoryExists(resource_path('utils'));

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

        $data = $fileSystem->get($envFilePath);

        $targets = [
            'APP_LOCALE=.*$' => 'APP_LOCALE=it',
            'APP_FAKER_LOCALE=.*$' => 'APP_FAKER_LOCALE=it_IT',
            'APP_URL=.*$' => 'APP_URL=http://localhost:8000'
        ];

        foreach ($targets as $from => $to) {
            if (mb_stripos($data, $from) !== false) {
                $data = preg_replace('@' . $from . '@', $to, $data);
    
                if (!is_null($data)) {
                    $fileSystem->put($envFilePath, $data);
                }
            }
        }

        /* --- */

        $targets = [
            'FZ_UTILS_INSTALLED=' => [
                'from' => 'FZ_UTILS_INSTALLED=.*$',
                'to' => 'FZ_UTILS_INSTALLED=true'
            ],
        ];

        $data .= PHP_EOL;

        foreach ($targets as $search => $fromTo) {
            if (mb_stripos($data, $search) !== false) {
                $data = preg_replace('@' . $fromTo['from'] . '@', $fromTo['to'], $data);
    
                if (!is_null($data)) {
                    $fileSystem->put($envFilePath, $data);
                }
            }
            else {
                $data .= (PHP_EOL . $fromTo['to']);
                $fileSystem->put($envFilePath, $data);
            }
        }
    }
}

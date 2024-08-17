<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

final class InstallCommand extends Command
{
    protected $signature = 'fz:install';

    protected $description = 'Install utilities';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $envFilePath = base_path('.env');

        if ($fileSystem->exists($envFilePath)) {
            $data = $fileSystem->get($envFilePath);

            if (mb_stripos($data, 'FZUTILS_INSTALLED') !== false) {
                $fzUtilsAlreadyInstalled = env('FZUTILS_INSTALLED');

                if (is_bool($fzUtilsAlreadyInstalled)) {
                    if ($fzUtilsAlreadyInstalled) {
                        $this->fail('Fzutils already installed');
                    }
                }
                else {
                    $this->fail('FZUTILS_INSTALLED into .env must be boolean');
                }
            }
        }
        else {
            $this->fail('.env file not found');
        }

        $fileSystem->ensureDirectoryExists(resource_path('views/components/layouts'));
        $fileSystem->copyDirectory(__DIR__.'/../../../assets/views', resource_path('views'));

        //$fileSystem->ensureDirectoryExists(resource_path('css'));
        //$fileSystem->copyDirectory(__DIR__.'/../../../assets/css', resource_path('css'));

        $fileSystem->ensureDirectoryExists(resource_path('js'));
        $fileSystem->copyDirectory(__DIR__.'/../../../assets/js', resource_path('js'));

        $fileSystem->ensureDirectoryExists(resource_path('sass'));
        $fileSystem->copyDirectory(__DIR__.'/../../../assets/sass', resource_path('sass'));

        $fileSystem->ensureDirectoryExists(base_path('lang'));
        $fileSystem->copyDirectory(__DIR__.'/../../../assets/lang', base_path('lang'));

        $fileSystem->ensureDirectoryExists(resource_path('assets'));

        $laravelBootstrapJsPath = resource_path('js/bootstrap.js');

        if ($fileSystem->exists($laravelBootstrapJsPath)) {
            $fileSystem->append($laravelBootstrapJsPath, "

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

        $data = $fileSystem->get($envFilePath);

        if (mb_stripos($data, 'APP_LOCALE=en') !== false) {
            $data = preg_replace('@APP_LOCALE=en@', 'APP_LOCALE=it_IT', $data);

            if (!is_null($data)) {
                $fileSystem->put($envFilePath, $data);
            }
        }

        if (mb_stripos($data, 'APP_FAKER_LOCALE=en_US') !== false) {
            $data = preg_replace('@APP_FAKER_LOCALE=en_US@', 'APP_FAKER_LOCALE=it_IT', $data);

            if (!is_null($data)) {
                $fileSystem->put($envFilePath, $data);
            }
        }

        /*if (mb_stripos($data, 'MAIL_MAILER=log') !== false) {
            $data = preg_replace('@MAIL_MAILER=log@', 'MAIL_MAILER=smtp # Per mailpit', $data);

            if (!is_null($data)) {
                $fileSystem->put($envFilePath, $data);
            }
        }*/

        /* --- */

        if (mb_stripos($data, 'FZUTILS_INSTALLED=') !== false) {
            $data = preg_replace('@FZUTILS_INSTALLED=.*$@', 'FZUTILS_INSTALLED=true', $data);

            if (!is_null($data)) {
                $fileSystem->put($envFilePath, $data);
            }
        }
        else {
            $fileSystem->append($envFilePath, PHP_EOL . 'FZUTILS_INSTALLED=true');
        }
    }
}

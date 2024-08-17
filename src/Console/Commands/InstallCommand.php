<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use RuntimeException;

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
            'APP_LOCALE=en' => 'APP_LOCALE=it_IT',
            'APP_FAKER_LOCALE=en_US' => 'APP_FAKER_LOCALE=it_IT',
            'APP_URL=http://localhost' => 'APP_URL=http://localhost:8000'
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

    /**
     * https://github.com/laravel/breeze/blob/2.x/src/Console/InstallCommand.php
     * 
     * Update the "package.json" file.
     *
     * @param  callable  $callback
     * @param  bool  $dev
     * @return void
     */
    protected static function updateNodePackages(callable $callback, $dev = true)
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT).PHP_EOL
        );
    }

    /**
     * https://github.com/laravel/breeze/blob/2.x/src/Console/InstallCommand.php
     * 
     * Run the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function runCommands($commands)
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> '.$e->getMessage().PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    '.$line);
        });
    }
}

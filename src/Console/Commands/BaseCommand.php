<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use RuntimeException;
use Illuminate\Filesystem\Filesystem;

class BaseCommand extends Command
{
    protected function checkEnvFlag(string $flagName, string $errorMessage) 
    {
        $fileSystem = new Filesystem();

        $envFilePath = base_path('.env');

        if ($fileSystem->exists($envFilePath)) {
            $data = $fileSystem->get($envFilePath);

            if (mb_stripos($data, $flagName) !== false) {
                $alreadyInstalled = env($flagName);

                if (is_bool($alreadyInstalled)) {
                    if ($alreadyInstalled) {
                        $this->fail($errorMessage);
                    }
                }
                else {
                    $this->fail($flagName . ' into .env must be boolean');
                }
            }
        }
        else {
            $this->fail('.env file not found');
        }
    }

    protected function updateEnvFile(array $targets)
    {
        $fileSystem = new Filesystem();

        $envFilePath = base_path('.env');

        $data = $fileSystem->get($envFilePath);

        foreach ($targets as $search => $fromTo) {
            if (mb_stripos($data, $search) !== false) {
                $data = preg_replace('@' . $fromTo['from'] . '@m', $fromTo['to'], $data);

                if (!is_null($data)) {
                    $fileSystem->put($envFilePath, $data);
                }
            }
        }
    }

    protected function updateEnvFileOrAppend(array $targets)
    {
        $fileSystem = new Filesystem();

        $envFilePath = base_path('.env');

        $data = $fileSystem->get($envFilePath);

        foreach ($targets as $search => $fromTo) {
            if (mb_stripos($data, $search) !== false) {
                $data = preg_replace('@' . $fromTo['from'] . '@m', $fromTo['to'], $data);
    
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

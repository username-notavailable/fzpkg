<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use RuntimeException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

class BaseCommand extends Command
{
    protected $logOutText = false;
    protected $logOutLabelledText = false;

    protected function disableOutLogs() : void
    {
        $this->logOutText = $this->logOutLabelledText = false;
    }

    protected function enableOutLogs() : void
    {
        $this->logOutText = $this->logOutLabelledText = true;
    }

    protected function outError(string $message, $verbosity = null) : void
    {
        $this->__outText('error', $message, $verbosity);
    }

    protected function outWarning(string $message, $verbosity = null) : void
    {
        $this->__outText('warning', $message, $verbosity);
    }

    protected function outInfo(string $message, $verbosity = null) : void
    {
        $this->__outText('info', $message, $verbosity);
    }

    protected function outComment(string $message, $verbosity = null) : void
    {
        $this->__outText('comment', $message, $verbosity);
    }

    protected function outText(string $message, $verbosity = null) : void
    {
        $this->__outText('text', $message, $verbosity);
    }

    private function __outText(string $type, string $message, $verbosity = null) : void
    {
        $logLevel = '';
    
        switch ($type) {
            case 'error':
            case 'warning':
            case 'info':
            case 'comment':
                $logLevel = $type === 'comment' ? 'info' : $type;

                $this->line($message, $type, $verbosity);
                break;
            
            case 'text':
                $logLevel = 'info';

                $this->line($message, null, $verbosity);
                break;

            default:
                $logLevel = 'info';

                $this->line($message, null, $verbosity);
                break;
        }

        if ($this->logOutText) {
            Log::log($logLevel, $message, [$this->signature, $type]);
        }
    }

    // --- //

    protected function outLabelledError(string $message, int $verbosity = \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL) : void
    {
        $this->__outLabelledText('error', $message, $verbosity);
    }

    protected function outLabelledWarning(string $message, int $verbosity = \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL) : void
    {
        $this->__outLabelledText('warning', $message, $verbosity);
    }

    protected function outLabelledInfo(string $message, int $verbosity = \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL) : void
    {
        $this->__outLabelledText('info', $message, $verbosity);
    }

    protected function outLabelledSuccess(string $message, int $verbosity = \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL) : void
    {
        $this->__outLabelledText('success', $message, $verbosity);
    }

    private function __outLabelledText(string $type, string $message, int $verbosity = \Symfony\Component\Console\Output\OutputInterface::VERBOSITY_NORMAL) : void
    {
        $logLevel = '';

        switch ($type) {
            case 'error':
                $logLevel = 'error';
                
                $this->outputComponents()->error($message, $verbosity);
                break;

            case 'warning':
                $logLevel = 'warning';
                
                $this->outputComponents()->warn($message, $verbosity);
                break;

            case 'info':
                $logLevel = 'info';
                
                $this->outputComponents()->info($message, $verbosity);
                break;
            
            case 'success':
                $logLevel = 'info';

                $this->outputComponents()->success($message, $verbosity);
                break;

            default:
                $logLevel = 'info';
                
                $this->outputComponents()->info($message, $verbosity);
                break;
        }

        if ($this->logOutLabelledText) {
            Log::log($logLevel, $message, [$this->signature, $type]);
        }
    }

    // --- //

    /**
     * 
     * Check (boolean) fz install FLAG_NAME into .env, fail with $errorMessage if FLAG_NAME=true
     *
     * @param  string $flagName
     * @param  string $errorMessage
     * @return void
     */
    protected function checkEnvFlag(string $flagName, string $errorMessage, ?string $envFilePath = null) : void
    {
        $filesystem = new Filesystem();

        $envFilePath = $envFilePath ?: base_path('.env');

        if ($filesystem->exists($envFilePath)) {
            $data = $filesystem->get($envFilePath);

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

    /**
     * 
     * Update CONF_VALUES into .env
     *
     * @param  array $targets [<CONF_NAME>]['from'] = old value, [<CONF_NAME>]['to'] = new value
     * @return void
     */
    protected function updateEnvFile(array $targets, ?string $envFilePath = null) : void
    {
        $filesystem = new Filesystem();

        $envFilePath = $envFilePath ?: base_path('.env');

        $data = $filesystem->get($envFilePath);

        foreach ($targets as $search => $fromTo) {
            if (mb_stripos($data, $search) !== false) {
                $data = preg_replace('@' . $fromTo['from'] . '@m', $fromTo['to'], $data);

                if (!is_null($data)) {
                    $filesystem->put($envFilePath, $data);
                }
            }
        }
    }

    /**
     * 
     * Update CONF_VALUES into .env, append [<CONF_NAME>]['to'] if CONF_NAME not exists
     *
     * @param  array $targets [<CONF_NAME>]['from'] = old value, [<CONF_NAME>]['to'] = new value
     * @return void
     */
    protected function updateEnvFileOrAppend(array $targets, ?string $envFilePath = null) : void
    {
        $filesystem = new Filesystem();

        $envFilePath = $envFilePath ?: base_path('.env');

        $data = $filesystem->get($envFilePath);

        foreach ($targets as $search => $fromTo) {
            if (mb_stripos($data, $search) !== false) {
                $data = preg_replace('@' . $fromTo['from'] . '@m', $fromTo['to'], $data);
    
                if (!is_null($data)) {
                    $filesystem->put($envFilePath, $data);
                }
            }
            else {
                $data .= (PHP_EOL . $fromTo['to']);
                $filesystem->put($envFilePath, $data);
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

<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ViewMakeCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use RuntimeException;
use Fuzzy\Fzpkg\FzpkgServiceProvider;

class MakeThemeViewCommand extends ViewMakeCommand
{
    protected $signature = 'fz:make:theme:view {name}';

    protected $description = 'Create a new theme view';

    protected $type = 'Theme View';

    private $themeName;

    public function __construct(Filesystem $files)
    {
        $this->themeName = null;

        parent::__construct($files);

        $this->getDefinition()->addOption(new InputOption('theme', null, InputOption::VALUE_REQUIRED, 'The theme name'));

        foreach ($this->getOptions() as $option) {
            $this->getDefinition()->addOption(new InputOption($option[0], $option[1], $option[2], $option[3], (isset($option[4]) ? $option[4] : null)));
        }
    }

    /**
     * Get the first view directory path from the application configuration.
     *
     * @param  string  $path
     * @return string
     */
    protected function viewPath($path = '')
    {
        return base_path('resources/' . $this->themeName . '/resources/views/' . $path);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (!empty($this->option('theme'))) {
            if (!$this->files->isDirectory(base_path('resources/' . $this->option('theme')))) {
                $this->fail('Theme "' . $this->option('theme') . '" not exists');
            }
            else {
                $this->themeName = $this->option('theme');
            }
        }

        if (is_null($this->themeName)) {
            if (config('app.env') === 'production') {
                $themes = FzpkgServiceProvider::getEnabledThemes();
            }
            else {
                $themes = FzpkgServiceProvider::getAvailableThemes();
            }

            if (count($themes) === 1) {
                $this->themeName = $themes[0];
            }
            else {
                $this->themeName = $this->choice('Theme selection?',
                    $themes,
                    0,
                    3
                );
            }
        }

        parent::handle();
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


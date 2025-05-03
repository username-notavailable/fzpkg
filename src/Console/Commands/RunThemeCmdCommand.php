<?php

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Fuzzy\Fzpkg\FzpkgServiceProvider;

class RunThemeCmdCommand extends BaseCommand
{
    protected $signature = 'fz:run:theme:cmd { --dev : Run "npm run dev" for the theme } { --build : Run "npm run build" for the theme } { --theme= : The theme name }';

    protected $description = 'Run "npm run [dev|build]" for the theme';

    private $themeName;
    protected $files;

    public function __construct(Filesystem $files)
    {
        $this->themeName = null;
        $this->files = $files;

        parent::__construct();
    }

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

        if ($this->option('build')) {
            $command = ['cmd' => ['node', 'node_modules/vite/bin/vite.js', '--config=vite-theme.config.js', 'build'], 'env' => ['FZ_SELECTED_THEME' => $this->themeName]];
        }
        else {
            $command = ['cmd' => ['node', 'node_modules/vite/bin/vite.js', '--config=vite-theme.config.js'], 'env' => ['FZ_SELECTED_THEME' => $this->themeName]];
        }

        if ((new Process($command['cmd'], base_path(), $command['env']))
            ->setTimeout(null)
            ->run(function ($type, $output) {
                $this->output->write($output);
            }) !== 0) {
                $this->outLabelledError('Run nmp command "' . implode(' ', $command['cmd']) . '" on theme "' . $this->themeName . '" failed...');
        }
    }
}


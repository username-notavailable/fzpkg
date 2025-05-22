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
        $filesystem = new Filesystem();

        $this->checkEnvFlag('FZ_UTILS_INSTALLED', 'Fz utils already installed');

        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/favicons', public_path());

        $filesystem->ensureDirectoryExists(resource_path('js'));
        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/js', resource_path('js'));

        $filesystem->ensureDirectoryExists(resource_path('sass'));
        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/sass', resource_path('sass'));

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

        $this->outLabelledSuccess('Fuzzy utils installed');
    }
}

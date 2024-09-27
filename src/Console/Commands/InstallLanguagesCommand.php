<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallLanguagesCommand extends BaseCommand
{
    protected $signature = 'fz:install:langs';

    protected $description = 'Publish all language files that are available for customization';

    public function handle(): void
    {
        $filesystem = new Filesystem();

        $this->checkEnvFlag('FZ_LANGS_INSTALLED', 'Fz langs already installed');

        $filesystem->ensureDirectoryExists(base_path('lang'));
        $filesystem->copyDirectory(__DIR__.'/../../../data/utils/lang', base_path('lang'));

        /* --- */

        $targets = [
            'FZ_LANGS_INSTALLED=' => [
                'from' => 'FZ_LANGS_INSTALLED=.*$',
                'to' => 'FZ_LANGS_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);

        $this->outLabelledSuccess('Languages installed');
    }
}

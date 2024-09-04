<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallLivewireLayoutsCommand extends BaseCommand
{
    protected $signature = 'fz:install:livewire:layouts';

    protected $description = 'Install livewire layouts';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $this->checkEnvFlag('FZ_LIVEWIRE_LAYOUTS_INSTALLED', 'Fz livewire layouts already installed');

        $fileSystem->ensureDirectoryExists(resource_path('views/components/layouts'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/livewire/components/layouts', resource_path('views/components/layouts'));

        /* --- */

        $targets = [
            'FZ_LIVEWIRE_LAYOUTS_INSTALLED=' => [
                'from' => 'FZ_LIVEWIRE_LAYOUTS_INSTALLED=.*$',
                'to' => 'FZ_LIVEWIRE_LAYOUTS_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);
    }
}

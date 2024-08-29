<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallStubsCommand extends BaseCommand
{
    protected $signature = 'fz:install:stubs';

    protected $description = 'Install stubs';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $fileSystem->ensureDirectoryExists(base_path('stubs'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/stubs', base_path('stubs'));
    }
}

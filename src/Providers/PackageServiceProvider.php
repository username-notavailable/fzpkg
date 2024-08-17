<?php 

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Providers;

use Illuminate\Support\ServiceProvider;
use Fuzzy\Fzpkg\Console\Commands\InstallCommand;

final class PackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                commands: [
                    InstallCommand::class,
                ],
            );
        }
    }
}

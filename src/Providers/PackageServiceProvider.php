<?php 

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Providers;

use Illuminate\Support\ServiceProvider;
use Fuzzy\Fzpkg\Console\Commands\InstallUtilsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallEventsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallStubsCommand;
use Fuzzy\Fzpkg\Console\Commands\MakeLivewireForm;

final class PackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/fz.php' => config_path('fz.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands(
                commands: [
                    InstallUtilsCommand::class,
                    InstallEventsCommand::class,
                    InstallStubsCommand::class,
                    MakeLivewireForm::class
                ],
            );
        }
    }
}

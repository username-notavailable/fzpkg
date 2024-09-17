<?php 

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Providers;

use Illuminate\Support\ServiceProvider;

use Fuzzy\Fzpkg\Console\Commands\InstallEventsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallLanguagesCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallLivewireLayoutsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallMiddlewaresCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallScrapersCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallStubsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallUtilsCommand;
use Fuzzy\Fzpkg\Console\Commands\MakeLivewireFormCommand;
use Fuzzy\Fzpkg\Console\Commands\MakeVoltComponentCommand;
use Fuzzy\Fzpkg\Console\Commands\RunScrapersCommand;

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
                    InstallEventsCommand::class,
                    InstallLanguagesCommand::class,
                    InstallLivewireLayoutsCommand::class,
                    InstallMiddlewaresCommand::class,
                    InstallScrapersCommand::class,
                    InstallStubsCommand::class,
                    InstallUtilsCommand::class,
                    MakeLivewireFormCommand::class,
                    MakeVoltComponentCommand::class,
                    RunScrapersCommand::class
                ],
            );
        }
    }
}

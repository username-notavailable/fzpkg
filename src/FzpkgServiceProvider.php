<?php 

declare(strict_types=1);

namespace Fuzzy\Fzpkg;

use Illuminate\Support\ServiceProvider;
//use Fuzzy\Fzpkg\Console\Commands\ExportSweetApiCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallEventsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallLanguagesCommand;
//use Fuzzy\Fzpkg\Console\Commands\InstallLivewireLayoutsCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallScrapersCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallStubsCommand;
//use Fuzzy\Fzpkg\Console\Commands\InstallSweetApiCommand;
use Fuzzy\Fzpkg\Console\Commands\InstallUtilsCommand;
//use Fuzzy\Fzpkg\Console\Commands\MakeLivewireFormCommand;
//use Fuzzy\Fzpkg\Console\Commands\MakeSweetApiEndpointsCommand;
use Fuzzy\Fzpkg\Console\Commands\MakeThemeComponentCommand;
use Fuzzy\Fzpkg\Console\Commands\MakeThemeViewCommand;
//use Fuzzy\Fzpkg\Console\Commands\MakeVoltComponentCommand;
//use Fuzzy\Fzpkg\Console\Commands\ReloadSweetApiCommand;
use Fuzzy\Fzpkg\Console\Commands\RunScrapersCommand;
//use Fuzzy\Fzpkg\Console\Commands\RunSweetApiCommand;
use Fuzzy\Fzpkg\Console\Commands\RunThemeCmdCommand;
//use Fuzzy\Fzpkg\Console\Commands\StatusSweetApiCommand; 
//use Fuzzy\Fzpkg\Console\Commands\StopSweetApiCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Auth\Middleware\Authenticate;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\Auth\KcGuard;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\{GlobalClientIdx, GuzzleClientHandlers};
use Illuminate\Support\Facades\Auth;
use Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Client;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;

final class FzpkgServiceProvider extends ServiceProvider
{
    public function register()
    {
        $filesystem = new Filesystem();

        if ($filesystem->missing(config_path('fz.php'))) {
            $this->mergeConfigFrom(
                __DIR__.'/../config/fz.php', 'fz'
            );
        }

        $this->app->singleton(GuzzleClientHandlers::class, function() {
            return new GuzzleClientHandlers();
        });

        $this->app->scoped(GlobalClientIdx::class, function() {
            return new GlobalClientIdx();
        });

        $this->app->singleton('__fzKcClientCacheRedisConnection', function() {
            $redis = new \Redis(config('fz.keycloak.client.cache.redis.init'));

            $redis->setOption(\Redis::OPT_PREFIX, config('fz.keycloak.client.cache.redis.prefix'));	

            foreach (config('fz.keycloak.client.cache.redis.options') as $optionName => $optionValue) {
                $redis->setOption($optionName, $optionValue);
            }

            return $redis;
        });

        $this->app->singleton('__fzKcClientCacheMemcachedConnection', function() {
            $memcached = new \Memcached(config('fz.keycloak.client.cache.memcached.init.persistent'));

            foreach (config('fz.keycloak.client.cache.memcached.options') as $optionName => $optionValue) {
                $memcached->setOptions(constant('\Memcached::'. $optionName), $optionValue);
            }

            $username = config('fz.keycloak.client.cache.memcached.init.auth')[0];
            $password = config('fz.keycloak.client.cache.memcached.init.auth')[1];

            if (!empty($username) && !empty($password)) {
                $memcached->setSaslAuthData($username, $password);
            }
            
            $memcached->addServers(config('fz.keycloak.client.cache.memcached.servers'));

            return $memcached;
        });
    }

    public function boot(): void
    {
        DB::prohibitDestructiveCommands(config('app.env') === 'production');

        if (config('fz.log.sql')) {
            DB::listen(function($query) {
                Log::info(
                    $query->sql,
                    [
                        'bindings' => $query->bindings,
                        'time' => $query->time
                    ]
                );
            });
        }

        /*Auth::provider('KcTokenProvider',function ($app,array $config) { 
            return new KcTokenProvider(Client::create());
        });*/

        Auth::extend('KcGuard', function ($app, $name, array $config) {
            return new KcGuard(Client::create());
        });

        Authenticate::redirectUsing(function($request) {
            return route('auth_login');
        });

        $this->publishes([
            __DIR__.'/../config/fz.php' => config_path('fz.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $commands = [
                //ExportSweetApiCommand::class,
                InstallEventsCommand::class,
                InstallLanguagesCommand::class,
                //InstallLivewireLayoutsCommand::class,
                InstallScrapersCommand::class,
                InstallStubsCommand::class,
                //InstallSweetApiCommand::class,
                InstallUtilsCommand::class,
                //MakeLivewireFormCommand::class,
                //MakeSweetApiEndpointsCommand::class,
                MakeThemeComponentCommand::class,
                MakeThemeViewCommand::class,
                //MakeVoltComponentCommand::class,
                RunScrapersCommand::class,
                RunThemeCmdCommand::class
            ];
            
            /*if (\Composer\InstalledVersions::isInstalled('laravel/octane')) {
                $commands = array_merge($commands, [
                    ReloadSweetApiCommand::class,
                    RunSweetApiCommand::class,
                    StatusSweetApiCommand::class,
                    StopSweetApiCommand::class
                ]);
            }*/

            $this->commands($commands);
        }
        else {
            if (config('fz.commons.forceHTTPS')) {
                URL::forceScheme('https');
            }

            $this->app->afterResolving(EncryptCookies::class, function ($middleware) {
                $middleware->disableFor(config('fz.default.localeCookieName'));
                $middleware->disableFor(config('fz.default.themeCookieName'));
            });

            if (config('fz.load.functions.blade')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions_blade.php';
            }

            if (config('fz.load.functions.helpers')) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions_helpers.php';
            }

            if (config('fz.load.routes')) {
                $this->loadRoutesFrom(__DIR__.'/routes.php');
            }
        }
    }

    public static function getAvailableLocales() : array
    {
        return array_keys(config('fz.i18n.locales'));
    }

    public static function elaborateSetLocaleFromCookie() : string
    {
        if (config('fz.load.cookies.locale')) {
            if (is_string(request()->cookie(config('fz.default.localeCookieName')))) {
                $useLocale = request()->cookie(config('fz.default.localeCookieName'));
            }
            else {
                if (request()->hasHeader('accept-language')) {
                    $useLocale = locale_accept_from_http(request()->header('accept-language'));
                }
                else {
                    Log::debug(__METHOD__ . ': Header "accept-language" not found');
                    $useLocale = null;
                }
    
                if (is_null($useLocale) || !$useLocale) {
                    $useLocale = config('fz.default.locale');
                }
            }
    
            if (!in_array($useLocale, self::getAvailableLocales())) {
                Log::info(__METHOD__ . ': Invalid locale "' . $useLocale . '"');

                $useLocale = array_key_first(config('fz.i18n.locales'));
            }
    
            App::setLocale($useLocale);
    
            return $useLocale;
        }
        else {
            return App::currentLocale();
        }
    }

    public static function getAvailableThemes() : array
    {
        $themes = [];

        foreach (glob(base_path('resources/*'), GLOB_ONLYDIR) as $theme) {
            $themes[] = basename($theme);
        }

        return $themes;
    }

    public static function getEnabledThemes() : array
    {
        return config('fz.ui.themes');
    }
    
    public static function elaborateSetThemeFromCookie() : string
    {
        if (config('fz.load.cookies.theme')) {
            if (is_string(request()->cookie(config('fz.default.themeCookieName')))) {
                $useTheme = request()->cookie(config('fz.default.themeCookieName'));
            }
            else {
                $useTheme = config('fz.default.ui.theme');
            }
    
            if (config('app.env') === 'production') {
                $themes = self::getEnabledThemes();
            }
            else {
                $themes = self::getAvailableThemes();
            }
    
            if (!in_array($useTheme, $themes)) {
                Log::debug(__METHOD__ . ': Invalid theme "' . $useTheme . '"; ' . (config('app.env') === 'production' ? 'theme not enabled' : 'directory not exists'));
                $useTheme = config('fz.ui.themes')[0];
            }
            
            $path = resource_path($useTheme . '/resources/views');
    
            $finder = new FileViewFinder(app()['files'], [$path]);
            View::setFinder($finder);
    
            Vite::useBuildDirectory($useTheme);
            Vite::useManifestFilename('manifest.json');
    
            return $useTheme;
        }
        else {
            return '';
        }        
    }
}

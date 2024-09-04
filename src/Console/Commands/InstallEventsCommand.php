<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Console\Commands;

use Illuminate\Filesystem\Filesystem;

final class InstallEventsCommand extends BaseCommand
{
    protected $signature = 'fz:install:events';

    protected $description = 'Install events';

    public function handle(): void
    {
        $fileSystem = new Filesystem();

        $this->checkEnvFlag('FZ_EVENTS_INSTALLED', 'Fz events already installed');

        $fileSystem->ensureDirectoryExists(app_path('Events'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/events', app_path('Events'));

        $fileSystem->ensureDirectoryExists(app_path('Listeners'));
        $fileSystem->copyDirectory(__DIR__.'/../../../data/listeners', app_path('Listeners'));

        /* --- */

        $targets = [
            'FZ_LOG_ADMIN_LOGIN_SUCCESS_REQUEST=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_SUCCESS_REQUEST=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_SUCCESS_REQUEST=false'
            ],
            'FZ_LOG_ADMIN_LOGIN_SUCCESS_LEVEL=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_SUCCESS_LEVEL=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_SUCCESS_LEVEL=debug'
            ],
            'FZ_LOG_ADMIN_LOGIN_FAIL_REQUEST=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_FAIL_REQUEST=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_FAIL_REQUEST=false'
            ],
            'FZ_LOG_ADMIN_LOGIN_FAIL_LEVEL=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_FAIL_LEVEL=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_FAIL_LEVEL=debug'
            ],
            'FZ_LOG_ADMIN_LOGIN_LOCKOUT_REQUEST=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_LOCKOUT_REQUEST=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_LOCKOUT_REQUEST=false'
            ],
            'FZ_LOG_ADMIN_LOGIN_LOCKOUT_LEVEL=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_LOCKOUT_LEVEL=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_LOCKOUT_LEVEL=debug'
            ],
            'FZ_LOG_ADMIN_LOGIN_MAX_ATTEMPTS=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_MAX_ATTEMPTS=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_MAX_ATTEMPTS=3'
            ],
            'FZ_LOG_ADMIN_LOGIN_DECAY_SECONDS=' => [
                'from' => 'FZ_LOG_ADMIN_LOGIN_DECAY_SECONDS=.*$',
                'to' => 'FZ_LOG_ADMIN_LOGIN_DECAY_SECONDS=60'
            ],
            'FZ_EVENTS_INSTALLED=' => [
                'from' => 'FZ_EVENTS_INSTALLED=.*$',
                'to' => 'FZ_EVENTS_INSTALLED=true'
            ],
        ];

        $this->updateEnvFileOrAppend($targets);
    }
}

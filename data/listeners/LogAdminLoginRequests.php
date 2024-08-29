<?php

namespace App\Listeners;

use App\Events\AdminLoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LogAdminLoginRequests
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AdminLoginRequest $event): void
    {
        $writeLog = false;
        $level = 'debug';

        switch ($event->result) {
            case 'admin-login-success':
                $writeLog = config('fz.log.admin.login.success.request', env('FZ_LOG_ADMIN_LOGIN_SUCCESS_REQUEST', false));
                $level = config('fz.log.admin.login.success.level', env('FZ_LOG_ADMIN_LOGIN_SUCCESS_LEVEL', 'debug'));
                break;

            case 'admin-login-fail':
                $writeLog = config('fz.log.admin.login.fail.request', env('FZ_LOG_ADMIN_LOGIN_FAIL_REQUEST', false));
                $level = config('fz.log.admin.login.fail.level', env('FZ_LOG_ADMIN_LOGIN_FAIL_LEVEL', 'debug'));
                break;

            case 'admin-login-lockout':
                $writeLog = config('fz.log.admin.login.lockout.request', env('FZ_LOG_ADMIN_LOGIN_LOCKOUT_REQUEST', false));
                $level = config('fz.log.admin.login.lockout.level', env('FZ_LOG_ADMIN_LOGIN_LOCKOUT_LEVEL', 'debug'));
                break;
        }

        if ($writeLog) {
            $key = hash('md5', $level . $event);

            RateLimiter::attempt(
                $key,
                (int)config('fz.log.admin.login.maxAttempts', env('FZ_LOG_ADMIN_LOGIN_MAX_ATTEMPTS', 3)),
                function() use ($level, $event) {
                    Log::log($level, 'AdminLoginRequest', [
                        'result' => $event->result,
                        'ips' => $event->request->ips(),
                        'userAgent' => $event->request->userAgent(),
                        'userId' => $event->userId,
                    ]);
                },
                (int)config('fz.log.admin.login.decaySeconds', env('FZ_LOG_ADMIN_LOGIN_DECAY_SECONDS', 60))
            );
        }
    }
}

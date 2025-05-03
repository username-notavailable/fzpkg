<?php

namespace App\Listeners;

use App\Events\LoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LogLoginRequests
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
    public function handle(LoginRequest $event): void
    {
        $writeLog = false;
        $level = 'debug';

        switch ($event->result) {
            case 'login-success':
                $writeLog = config('fz.log.login.success.request');
                $level = config('fz.log.login.success.level');
                break;

            case 'login-fail':
                $writeLog = config('fz.log.login.fail.request');
                $level = config('fz.log.login.fail.level');
                break;

            case 'login-lockout':
                $writeLog = config('fz.log.login.lockout.request');
                $level = config('fz.log.login.lockout.level');
                break;
        }

        if ($writeLog) {
            $key = hash('md5', $level . $event);

            RateLimiter::attempt(
                $key,
                (int)config('fz.log.login.maxAttempts'),
                function() use ($level, $event) {
                    Log::log($level, 'LoginRequest', [
                        'result' => $event->result,
                        'ips' => $event->request->ips(),
                        'userAgent' => $event->request->userAgent(),
                        'userId' => $event->userId,
                        'userIdFrom' => $event->userIdFrom
                    ]);
                },
                (int)config('fz.log.login.decaySeconds')
            );
        }
    }
}

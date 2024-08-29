<?php

return [
    'log' => [
        'admin' => [
            'login' => [
                'success' => [
                    'request' => env('FZ_LOG_ADMIN_LOGIN_SUCCESS_REQUEST', false),
                    'level' => env('FZ_LOG_ADMIN_LOGIN_SUCCESS_LEVEL', 'debug'),
                ],
                'fail' => [
                    'request' => env('FZ_LOG_ADMIN_LOGIN_FAIL_REQUEST', false),
                    'level' => env('FZ_LOG_ADMIN_LOGIN_FAIL_LEVEL', 'debug'),
                ],
                'lockout' => [
                    'request' => env('FZ_LOG_ADMIN_LOGIN_LOCKOUT_REQUEST', false),
                    'level' => env('FZ_LOG_ADMIN_LOGIN_LOCKOUT_LEVEL', 'debug'),
                ],
                'maxAttempts' => env('FZ_LOG_ADMIN_LOGIN_MAX_ATTEMPTS', 5),
                'decaySeconds' => env('FZ_LOG_ADMIN_LOGIN_DECAY_SECONDS', 50)
            ]
        ]
    ]
];
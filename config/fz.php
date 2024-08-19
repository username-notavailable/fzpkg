<?php

return [
    'log' => [
        'login' => [
            'success' => [
                'request' => env('FZ_LOG_LOGIN_SUCCESS_REQUEST', false),
                'level' => env('FZ_LOG_LOGIN_SUCCESS_LEVEL', 'debug'),
            ],
            'fail' => [
                'request' => env('FZ_LOG_LOGIN_FAIL_REQUEST', false),
                'level' => env('FZ_LOG_LOGIN_FAIL_LEVEL', 'debug'),
            ],
            'lockout' => [
                'request' => env('FZ_LOG_LOGIN_LOCKOUT_REQUEST', false),
                'level' => env('FZ_LOG_LOGIN_LOCKOUT_LEVEL', 'debug'),
            ]
        ]
    ]
];
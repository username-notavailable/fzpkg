<?php

return [
    'commons' => [
        'forceHTTPS' => env('FZ_FORCE_HTTPS', false)
    ],
    'load' =>[
        'functions' => [
            'blade' => env('FZ_LOAD_FUNCTIONS_BLADE', true),
            'helpers' => env('FZ_LOAD_FUNCTIONS_HELPERS', true)
        ],
        'cookies' => [
            'locale' => env('FZ_LOAD_COOKIES_LOCALE', true),
            'theme' => env('FZ_LOAD_COOKIES_THEME', true),
        ],
        'routes' => env('FZ_LOAD_ROUTES', true)
    ],
    'ui' => [
        'themes' => [
            'default'
        ]
    ],
    'keycloak' => [
        'login' => [
            'type' => env('FZ_KC_LOGIN_TYPE', 'Frontend'), // Frontend, Backend
            'loginViewName' => env('FZ_KC_LOGIN_LOGIN_VIEW_NAME', 'login'),
            'issuesViewName' => env('FZ_KC_LOGIN_ISSUES_VIEW_NAME', 'errors.generic'),
            'postRouteName' => env('FZ_KC_LOGIN_POST_ROUTE_NAME', 'index'),
            
        ],
        'logout' => [
            'allowClientIdxFromRequest' => env('FZ_KC_LOGOUT_ALLOW_CLIENTIDX_FROM_REQUEST', false),
            'skipConfirmation' => env('FZ_KC_LOGOUT_SKIP_CONFIRMATION', false),
            'postRouteName' => env('FZ_KC_LOGOUT_POST_ROUTE_NAME', 'index'),
        ],
        'client' => [
            'idxs' => [
                'default' => [
                    'loginHostname' => env('FZ_KC_LOGIN_HOSTNAME', ''),
                    'authType' => env('FZ_KC_AUTH_TYPE', 'ClientSecret'), // ClientSecret, SignedJwt, SignedJwtClientSecret
                    'realmName' => env('FZ_KC_REALM_NAME', ''),
                    'clientId' => env('FZ_KC_CLIENT_ID', ''),
                    'clientSecret' => env('FZ_KC_CLIENT_SECRET', ''),
                    'authPayload' => [],
                    'authAlg' => env('FZ_KC_AUTH_ALG', ''),
                    'pubKeyName' => env('FZ_KC_PUB_KEY_NAME', ''),
                    'pvtKeyName' => env('FZ_KC_PVT_KEY_NAME', ''),
                    'certName' => env('FZ_KC_CERT_NAME', '')
                ]
            ]
        ],
    ],
    'i18n' => [
        'locales' => [
            'it_IT' => [
                'name' => 'Italiano',
                'code' => 'it_IT',
                'flagIco' => 'ita'
            ],
            'en_US' => [
                'name' => 'English',
                'code' => 'en_US',
                'flagIco' => 'usa'
            ],
            'de_DE' => [
                'name' => 'Deutsch',
                'code' => 'de_DE',
                'flagIco' => 'deu'
            ],
            'es_ES' => [
                'name' => 'Español',
                'code' => 'es_ES',
                'flagIco' => 'esp'
            ],
            'fr_FR' => [
                'name' => 'Français',
                'code' => 'fr_FR',
                'flagIco' => 'fra'
            ],
            'ru_RU' => [
                'name' => 'Русский',
                'code' => 'ru_RU',
                'flagIco' => 'rus'
            ],
            'pt_PT' => [
                'name' => 'Português',
                'code' => 'pt_PT',
                'flagIco' => 'prt'
            ]
        ],
    ],
    'log' => [
        'testLogEnabled' => env('FZ_LOG_TEST_LOG_ENABLED', false),
        'sql' => env('FZ_LOG_SQL', false),
        'login' => [
            'success' => [
                'request' => env('FZ_LOG_LOGIN_SUCCESS_REQUEST', true),
                'level' => env('FZ_LOG_LOGIN_SUCCESS_LEVEL', 'debug'),
            ],
            'fail' => [
                'request' => env('FZ_LOG_LOGIN_FAIL_REQUEST', true),
                'level' => env('FZ_LOG_LOGIN_FAIL_LEVEL', 'debug'),
            ],
            'lockout' => [
                'request' => env('FZ_LOG_LOGIN_LOCKOUT_REQUEST', true),
                'level' => env('FZ_LOG_LOGIN_LOCKOUT_LEVEL', 'debug'),
            ],
            'maxAttempts' => env('FZ_LOG_LOGIN_MAX_ATTEMPTS', 5),
            'decaySeconds' => env('FZ_LOG_LOGIN_DECAY_SECONDS', 50)
        ]
    ],
    'default' => [
        'locale' => env('FZ_DEFAULT_LOCALE', 'it_IT'),
        'localeCookieName' => env('FZ_DEFAULT_LOCALE_COOKIE_NAME', '__fz_lcl__'),
        'themeCookieName' => env('FZ_DEFAULT_THEME_COOKIE_NAME', '__fz_thm__'),
        'ui' => [
            'theme' => env('FZ_DEFAULT_UI_THEME', 'default')
        ],
        'keycloak' => [
            'authGuardName' => 'keyCloak',
            'clientIdx' => env('FZ_DEFAULT_KEYCLOAK_CLIENT_IDX', 'default'),
            'onlyGuestFailRouteName' => env('FZ_DEFAULT_KEYCLOAK_ONLY_GUEST_FAIL_ROUTE_NAME', 'index')
        ],
        'sweetapi' => [
            'info' => [
                'title' => env('FZ_DEFAULT_SWEETAPI_TITLE', 'SweetAPI'),
                'description' => env('FZ_DEFAULT_SWEETAPI_DESCRIPTION', ''),
                'termsOfService' => env('FZ_DEFAULT_SWEETAPI_TERMS_OF_SERVICE', ''),
                'contact' => [
                    'name' => env('FZ_DEFAULT_SWEETAPI_CONTACT_NAME', ''),
                    'url' => env('FZ_DEFAULT_SWEETAPI_CONTACT_URL', ''),
                    'email' => env('FZ_DEFAULT_SWEETAPI_CONTACT_EMAIL', '')
                ],
                'license' => [
                    'name' => env('FZ_DEFAULT_SWEETAPI_LICENSE_NAME', ''),
                    'url' => env('FZ_DEFAULT_SWEETAPI_LICENSE_URL', '')
                ],
                'version' => env('FZ_DEFAULT_SWEETAPI_OPEN_API_DOC_VERSION', '1.0.0'),
                'externalDocs' => [
                    'url' => env('FZ_DEFAULT_SWEETAPI_EXT_DOC_URL', ''),
                    'description' => env('FZ_DEFAULT_SWEETAPI_EXT_DESCRIPTION', '')
                ]
            ],
            'repleceJsonIfExists' => env('FZ_DEFAULT_SWAGGER_REPLACE_JSON_IF_EXISTS', false),
            'jsonFilePath' => env('FZ_DEFAULT_SWAGGER_JSON_FILE_PATH', 'sweetapi/swagger.json')
        ]
    ]
];
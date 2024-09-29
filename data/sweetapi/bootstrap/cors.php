<?php

/*

src:  https://github.com/fruitcake/php-cors/tree/master
info: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS

Option                  Description                                             Default value
---------------------------------------------------------------------------------------------
allowedMethods 	        Matches the request method. 	                        []
allowedOrigins 	        Matches the request origin. 	                        []
allowedOriginsPatterns 	Matches the request origin with preg_match. 	        []
allowedHeaders 	        Sets the Access-Control-Allow-Headers response header. 	[]
exposedHeaders 	        Sets the Access-Control-Expose-Headers response header. []
maxAge 	                Sets the Access-Control-Max-Age response header. 	    0
supportsCredentials 	Sets the Access-Control-Allow-Credentials header. 	    false
---------------------------------------------------------------------------------------------

example:

[
    'allowedHeaders'         => ['x-allowed-header', 'x-other-allowed-header'],
    'allowedMethods'         => ['DELETE', 'GET', 'POST', 'PUT'],
    'allowedOrigins'         => ['http://localhost', 'https://*.example.com'],
    'allowedOriginsPatterns' => ['/localhost:\d/'],
    'exposedHeaders'         => ['Content-Encoding'],
    'maxAge'                 => 0,
    'supportsCredentials'    => false,
]

*/

return [
    'allowedHeaders'         => ['*'],
    'allowedMethods'         => ['*'],
    'allowedOrigins'         => ['http://127.0.0.1:8000'],
    'allowedOriginsPatterns' => ['/127\.0\.0\.1:\d/'],
    'exposedHeaders'         => ['Content-Encoding'],
    'maxAge'                 => 0,
    'supportsCredentials'    => false,
];
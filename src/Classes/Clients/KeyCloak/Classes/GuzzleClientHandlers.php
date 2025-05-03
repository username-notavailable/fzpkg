<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\HandlerStack;

class GuzzleClientHandlers
{
    private static $handlers = null;

    public function getHandler(string $handlerName, ?string $handlerClass = null, array $options = []) : HandlerStack
    {
        if (is_null(static::$handlers)) {
            Log::debug(__METHOD__ . ': Init "static::$handlers"');
            static::$handlers = [];
        }

        if (!array_key_exists($handlerName, static::$handlers)) {
            Log::debug(__METHOD__ . ': Add "' . $handlerName . '" to "static::$handlers"');

            if (is_null($handlerClass)) {
                Log::debug(__METHOD__ . ': $handlerClass is null for "' . $handlerName . '", add "GuzzleClientHandler::create()" to "static::$handlers"');
                $handlerStack = new GuzzleClientHandler();
            }
            else {
                $handlerStack = new $handlerClass();

                if (!$handlerStack instanceof GuzzleClientHandlerInterface) {
                    Log::warning(__METHOD__ . ': $handlerClass for "' . $handlerName . '", must implement "GuzzleClientHandlerInterface", add "GuzzleClientHandler::create()" to "static::$handlers"');
                    $handlerStack = new GuzzleClientHandler();
                }
            }

            static::$handlers[$handlerName] = $handlerStack->create($options);
        }
        else {
            Log::debug(__METHOD__ . ': "' . $handlerName . '" already set into "static::$handlers"');
        }
        
        return static::$handlers[$handlerName];
    }

    public function handlerExists(string $handlerName) : bool
    {
        if (is_null(static::$handlers)) {
            static::$handlers = [];
        }

        return array_key_exists($handlerName, static::$handlers);
    }
}


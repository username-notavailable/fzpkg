<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\ClientCache;

use Illuminate\Support\Facades\Log;

class RedisCache implements CacheInterface
{
    private $connection;

    public function __construct()
    {
        $this->connection = new \Redis(config('fz.keycloak.client.cache.redis.init'));

        $this->connection->setOption(\Redis::OPT_PREFIX, config('fz.keycloak.client.cache.redis.prefix'));	

        foreach (config('fz.keycloak.client.cache.redis.options') as $optionName => $optionValue) {
            $this->connection->setOption($optionName, $optionValue);
        }
    }

    public function SET(string $key, string $value, int $expirationSeconds) : bool
    {
        return $this->connection->rawCommand('SET', $key, $value, 'EX', $expirationSeconds);
    }

    public function GET(string $key) : ?string
    {
        $value = $this->connection->rawCommand('GET', $key);

        if (!is_string($value)) {
            Log::debug(__METHOD__ . ': key = ' . $key . ' value is not a string');
            return null;
        }

        return $value;
    }

    public function EXISTS(string $key) : bool
    {
        return $this->connection->rawCommand('EXISTS', $key) === 1;
    }

    public function DEL(string $key) : bool
    {
        return $this->connection->rawCommand('DEL', $key) === 1;
    }

    public function LOCK(string $key, string $value = 'true', float $timeOffset = 20.0, int $sleepMicroseconds = 100000) : bool
    {
        $key = 'lock:' . $key;
        $time = microtime(true);
        $exit_time = $time + $timeOffset;

        if ($sleepMicroseconds <= 0) {
            $sleepMicroseconds = 100000;
        }

        Log::debug(__METHOD__ . ': Requested lock for key "' . $key . '"');

        do 
        {
            // Lock Redis with EX and NX

            if ($this->connection->rawCommand('SET', $key, $value, 'NX', 'EX', (int)$timeOffset)) {
                Log::debug(__METHOD__ . ': Lock done for key "' . $key . '"');
                return true;
            }
            else {
                Log::debug(__METHOD__ . ': Wait lock for key "' . $key . '"');
            }

            usleep($sleepMicroseconds);

        } while (microtime(true) < $exit_time);

        Log::debug(__METHOD__ . ': Lock failed for key "' . $key . '"');

        return false;
    }

    public function UNLOCK(string $key, int $sleepMicroseconds = 100000) : bool
    {
        $key = 'lock:' . $key;

        if ($sleepMicroseconds <= 0) {
            $sleepMicroseconds = 100000;
        }

        Log::debug(__METHOD__ . ': Requested unlock for key "' . $key . '" ');

        do
        {
            $done = true;

            if ($this->EXISTS($key)) {
                $done = $this->DEL($key);
            }

            if (!$done) {
                Log::debug(__METHOD__ . ': Wait unlock for key "' . $key . '"');
                usleep($sleepMicroseconds);
            }

        } while (!$done);

        if ($done) {
            Log::debug(__METHOD__ . ': Unlock done for key "' . $key . '"');
        }
        else {
            Log::debug(__METHOD__ . ': Unlock failed for key "' . $key . '"');
        }

        return $done;
    }
}
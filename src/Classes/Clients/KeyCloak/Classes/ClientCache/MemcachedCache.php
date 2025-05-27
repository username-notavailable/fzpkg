<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\ClientCache;

use Illuminate\Support\Facades\Log;

class MemcachedCache implements CacheInterface
{
    private $connection;

    public function __construct()
    {
        $this->connection = new \Memcached(config('fz.keycloak.client.cache.memcached.init.persistent'));

        foreach (config('fz.keycloak.client.cache.memcached.options') as $optionName => $optionValue) {
            $this->connection->setOption(constant('\Memcached::'. $optionName), $optionValue);
        }

        $username = config('fz.keycloak.client.cache.memcached.init.auth')[0];
        $password = config('fz.keycloak.client.cache.memcached.init.auth')[1];

        if (!empty($username) && !empty($password)) {
            $this->connection->setSaslAuthData($username, $password);
        }
        
        $this->connection->addServers(config('fz.keycloak.client.cache.memcached.servers'));

        return $this->connection;
    }

    public function SET(string $key, string $value, int $expirationSeconds) : bool
    {
        return $this->connection->set($key, $value, time() + $expirationSeconds);
    }

    public function GET(string $key) : ?string
    {
        $value = $this->connection->get($key);

        if (!is_string($value)) {
            Log::debug(__METHOD__ . ': key = ' . $key . ' value is not a string');
            return null;
        }

        return $value;
    }

    public function EXISTS(string $key) : bool
    {
        $this->connection->get($key);

        return $this->connection->getResultCode() !== \Memcached::RES_NOTFOUND;
    }

    public function DEL(string $key) : bool
    {
        return $this->connection->delete($key, 0);
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
            // Lock Memcached con add

            if ($this->connection->add($key, $value, (int)$timeOffset)) {
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
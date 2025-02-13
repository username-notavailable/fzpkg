<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Redis;

use Illuminate\Redis\Connections\Connection;

class RedisLock
{
    static public function lock(Connection $redis, string $key, string $value = 'true') 
    {
        $time = microtime(true);
        $exit_time = $time + 10;
        $sleep = 10000;

        do 
        {
            // Lock Redis with EX and NX

            $redis->multi();
            $redis->set('lock:' . $key, $value, 'EX', 10, 'NX');
            $ret = $redis->exec();

            if ($ret[0] === true) {
                return true;
            }

            usleep($sleep);

        } while (microtime(true) < $exit_time);

        return false;
    }

    static public function unlock(Connection $redis, string $key) 
    {
        $redis->multi();
        $redis->del('lock:' . $key);
        $redis->exec();
    }
}
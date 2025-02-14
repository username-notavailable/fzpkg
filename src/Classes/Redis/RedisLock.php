<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Redis;

use Illuminate\Redis\Connections\Connection;

class RedisLock
{
    static public function lock(Connection $redis, string $key, int $timeOffset = 20, string $value = 'true') : bool
    {
        $key = 'lock:' . $key;
        $time = microtime(true);
        $exit_time = $time + $timeOffset;
        $sleep = 100000;

        do 
        {
            // Lock Redis with EX and NX

            $ret = $redis->executeRaw(['SET', $key, $value, 'NX', 'EX', $timeOffset]);

            if ($ret === 'OK') {
                return true;
            }

            usleep($sleep);

        } while (microtime(true) < $exit_time);

        return false;
    }

    static public function unlock(Connection $redis, string $key) : bool
    {
        $key = 'lock:' . $key;
        $sleep = 100000;

        do
        {
            $done = true;

            if($redis->executeRaw(['EXISTS', $key]) === 1) {
                $done = $redis->executeRaw(['DEL', $key]) === 1;
            }

            if (!$done) {
                usleep($sleep);
            }

        } while (!$done);

        return $done;
    }
}
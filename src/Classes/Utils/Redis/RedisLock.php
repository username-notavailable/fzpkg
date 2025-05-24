<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Utils\Redis;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;

class RedisLock
{
    static public function lock(Connection $redis, string $key, int $timeOffset = 20, string $value = 'true') : bool
    {
        $key = 'lock:' . $key;
        $time = microtime(true);
        $exit_time = $time + $timeOffset;
        $sleep = 100000;

        Log::debug(__METHOD__ . ': Requested lock for key "' . $key . '"');

        do 
        {
            // Lock Redis with EX and NX

            $ret = $redis->executeRaw(['SET', $key, $value, 'NX', 'EX', $timeOffset]);

            if ($ret === 'OK') {
                Log::debug(__METHOD__ . ': Lock done for key "' . $key . '"');
                return true;
            }
            else {
                Log::debug(__METHOD__ . ': Wait lock for key "' . $key . '"');
            }

            usleep($sleep);

        } while (microtime(true) < $exit_time);

        Log::debug(__METHOD__ . ': Lock failed for key "' . $key . '"');

        return false;
    }

    static public function unlock(Connection $redis, string $key) : bool
    {
        $key = 'lock:' . $key;
        $sleep = 100000;

        Log::debug(__METHOD__ . ': Requested unlock for key "' . $key . '" ');

        do
        {
            $done = true;

            if($redis->executeRaw(['EXISTS', $key]) === 1) {
                $done = $redis->executeRaw(['DEL', $key]) === 1;
            }

            if (!$done) {
                Log::debug(__METHOD__ . ': Wait unlock for key "' . $key . '"');
                usleep($sleep);
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
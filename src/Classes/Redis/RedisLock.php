<?php

namespace Fuzzy\Fzpkg\Classes\Redis;

class RedisLock
{
    static public function lock($redis, $key, $value = "true") 
    {
        $time = microtime(true);
        $exit_time = $time + 10;
        $sleep = 10000;

        do 
        {
            // Lock Redis with PX and NX

            $redis->multi();
            $redis->set('lock:' . $key, $value, array('nx', 'ex' => 10));
            $ret = $redis->exec();

            if ($ret[0] == true) {
                return true;
            }

            usleep($sleep);

        } while (microtime(true) < $exit_time);

        return false;
    }

    static public function unlock($redis, $key) 
    {
        $redis->multi();
        $redis->del("lock:" . $key);
        $redis->exec();
    }
}
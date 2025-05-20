<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes\ClientCache;

interface CacheInterface
{
    public function SET(string $key, string $value, int $expirationSeconds) : bool;
    public function GET(string $key) : ?string;
    public function EXISTS(string $key) : bool;
    public function DEL(string $key) : bool;

    public function LOCK(string $key, string $value = 'true', float $timeOffset = 20.0, int $sleepMicroseconds = 100000) : bool;
    public function UNLOCK(string $key, int $sleepMicroseconds = 100000) : bool;
}


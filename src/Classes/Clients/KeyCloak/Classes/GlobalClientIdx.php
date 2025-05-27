<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Illuminate\Support\Facades\Log;

class GlobalClientIdx
{
    protected $name;

    public function __construct(string $defaultClientIdx)
    {
        $this->name = $defaultClientIdx;
    }

    public function get() : string
    {
        if (empty($this->name)) {
            Log::debug(__METHOD__ . ': Selected clientIdx is null, returned "' . config('fz.default.keycloak.clientIdx') . '"');
            return config('fz.default.keycloak.clientIdx');
        }

        if (empty(config('fz.keycloak.client.idxs')[$this->name] ?? [])) {
            Log::error(__METHOD__ . ': Selected clientIdx not exists ("' . $this->name . '"');
            throw new \Exception('Selected clientIdx not exists ("' . $this->name . '"');
        }

        return $this->name;
    }

    public function set(string $name) : void
    {
        if (empty(config('fz.keycloak.client.idxs')[$name] ?? [])) {
            Log::error(__METHOD__ . ': Selected clientIdx not exists ("' . $name . '")');
            throw new \Exception('Selected clientIdx not exists ("' . $name . '"');
        }

        Log::debug(__METHOD__ . ': Global clientIdx set to "' . $name . '"');
        $this->name = $name;
    }
}


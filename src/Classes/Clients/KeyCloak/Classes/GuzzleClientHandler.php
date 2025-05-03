<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use GuzzleHttp\HandlerStack;

class GuzzleClientHandler implements GuzzleClientHandlerInterface
{
    public function create(array $options) : HandlerStack
    {
        return HandlerStack::create();
    }
}


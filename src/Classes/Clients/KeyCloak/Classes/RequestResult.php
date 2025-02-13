<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak\Classes;

use Psr\Http\Message\ResponseInterface;

class RequestResult
{
    public bool $fromCache;
    public ?ResponseInterface $rawResponse;
    public array $json;

    public function __construct(bool $fromCache, ?ResponseInterface $rawResponse, array $json = [])
    {
        $this->fromCache = $fromCache;
        $this->rawResponse = $rawResponse;
        $this->json = $json;
    }
}
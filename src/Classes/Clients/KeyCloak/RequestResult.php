<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\KeyCloak;

use Psr\Http\Message\ResponseInterface;

class RequestResult
{
    public ResponseInterface $rawResponse;
    public array $json;

    public function __construct(ResponseInterface $rawResponse, array $json = [])
    {
        $this->rawResponse = $rawResponse;
        $this->json = $json;
    }
}
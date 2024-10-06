<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Classes;

use Illuminate\Http\JsonResponse as LaravelJsonResponse;

class JsonResponse extends LaravelJsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [], $options = 0, $json = false)
    {
        $this->encodingOptions = $options;

        parent::__construct($data, $status, $headers, $json);
    }
}

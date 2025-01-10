<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Scrapers;

class FinalizeResult
{ 
    public function __construct(public bool $hasError = false, public string $message = '')
    {
    }
}
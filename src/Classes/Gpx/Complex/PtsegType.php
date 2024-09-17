<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class PtsegType
{
    public array $ptTypes;

    public function __construct(array $ptTypes = [])
    {
        $this->ptTypes = $ptTypes;
    }
}
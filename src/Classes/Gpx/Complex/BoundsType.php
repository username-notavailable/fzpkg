<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class BoundsType
{
    public float $minlat;
    public float $minlon;
    public float $maxlat;
    public float $maxlon;

    public function __construct(float $minlat, float $minlon, float $maxlat, float $maxlon)
    {
        $this->minlat = $minlat;
        $this->minlon = $minlon;
        $this->maxlat = $maxlat;
        $this->maxlon = $maxlon;
    }
}
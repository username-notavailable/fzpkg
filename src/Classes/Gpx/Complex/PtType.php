<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\Simple\LatitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\LongitudeType;

class PtType
{
    public float $lat;
    public float $lon;
    public float $ele;
    public float $time;

    public function __construct(float $lat, float $lon, float $ele, float $time)
    {
        $this->lat = $lat;
        $this->lon = $lon;
        $this->ele = $ele;
        $this->time = $time;
    }
}
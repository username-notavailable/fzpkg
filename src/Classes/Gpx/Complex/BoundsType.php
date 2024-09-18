<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class BoundsType
{
    public float $minlat;
    public float $minlon;
    public float $maxlat;
    public float $maxlon;

    public function __construct(\DOMXPath &$xPath, \DOMNode &$currentNode)
    {
        $this->minlat = floatval($currentNode->getAttribute('minlat'));
        $this->minlon = floatval($currentNode->getAttribute('minlon'));
        $this->maxlat = floatval($currentNode->getAttribute('maxlat'));
        $this->maxlon = floatval($currentNode->getAttribute('maxlon'));
    }
}
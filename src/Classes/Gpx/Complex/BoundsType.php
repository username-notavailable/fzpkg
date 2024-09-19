<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\Simple\LatitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\LongitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class BoundsType extends BaseType
{
    public ?LatitudeType $minlat;
    public ?LongitudeType $minlon;
    public ?LatitudeType $maxlat;
    public ?LongitudeType $maxlon;

    public function __construct()
    {
        $this->minlat = new LatitudeType();
        $this->minlon = new LongitudeType();
        $this->maxlat = new LatitudeType();
        $this->maxlon = new LongitudeType();
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->minlat = new LatitudeType($this->readAttributeAsDecimal('minlat', $currentNode));
        $this->minlon = new LongitudeType($this->readAttributeAsDecimal('minlon', $currentNode));
        $this->maxlat = new LatitudeType($this->readAttributeAsDecimal('maxlat', $currentNode));
        $this->maxlon = new LongitudeType($this->readAttributeAsDecimal('maxlon', $currentNode));

        return $this;
    }

    public function __toString() : string
    {
        $string = '<bounds ';

        if (!is_null($this->minlat)) {
            $string .= 'minlat="' . $this->minlat . '" ';
        }

        if (!is_null($this->minlon)) {
            $string .= 'minlon="' . $this->minlon . '" ';
        }

        if (!is_null($this->maxlat)) {
            $string .= 'maxlat="' . $this->maxlat . '" ';
        }

        if (!is_null($this->maxlon)) {
            $string .= 'maxlon="' . $this->maxlon . '" ';
        }

        return trim($string) . '></bounds>';
    }
}
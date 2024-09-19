<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\Simple\LatitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\LongitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\BaseType;
use ErrorException;

class PtType extends BaseType
{
    private LatitudeType $lat;
    private LongitudeType $lon;
    public ?float $ele;
    public ?string $time;

    public function __construct()
    {
        $this->lat = new LatitudeType();
        $this->lon = new LongitudeType();
        $this->ele = null;
        $this->time = null;
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->lat = new LatitudeType($this->readAttributeAsDecimal('lat', $currentNode));
        $this->lon = new LongitudeType($this->readAttributeAsDecimal('lon', $currentNode));
        $this->ele = $this->evaluateDecimal($xPath, './ns:ele', $currentNode);
        $this->time = $this->evaluateDateTime($xPath, './ns:time', $currentNode);

        return $this;
    }

    public function __toString() : string
    {
        $string = '<pt ';

        if (!is_null($this->lat)) {
            $string .= 'lat="' . $this->lat . '" ';
        }

        if (!is_null($this->lon)) {
            $string .= 'lon="' . $this->lon . '" ';
        }

        $string = trim($string) . '>';

        if (!is_null($this->ele)) {
            $string .= '<ele>' . $this->ele . '</ele>';
        }

        if (!is_null($this->time)) {
            $string .= '<time>' . $this->time . '</time>';
        }

        $string .= '</pt>';

        return $string;
    }

    public function __get(string $name) : mixed
    {
        switch ($name)
        {
            case 'lat':
                return $this->lat->value;
                break;

            case 'lon':
                return $this->lon->value;
                break;

            default:
                throw new ErrorException(__METHOD__ . ': invalid __get() name (' . $name . ')');
                break;
        }
    }

    public function __set(string $name, mixed $value) : void
    {
        switch ($name)
        {
            case 'lat':
                $this->lat->value = $value;
                break;

            case 'lon':
                $this->lon->value = $value;
                break;

            default:
                throw new ErrorException(__METHOD__ . ': invalid __set() name (' . $name . ')');
                break;
        }
    }
}
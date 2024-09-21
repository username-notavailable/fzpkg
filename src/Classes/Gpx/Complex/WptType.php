<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\Simple\LatitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\LongitudeType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\DegreesType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\FixType;
use Fuzzy\Fzpkg\Classes\Gpx\Simple\DgpsStationType;
use Fuzzy\Fzpkg\Classes\Gpx\BaseType;
use ErrorException;

class WptType extends BaseType
{
    private LatitudeType $lat;
    private LongitudeType $lon;
    public ?float $ele;
    public ?string $time;
    private ?DegreesType $magvar;
    public ?float $geoidheight;
    public ?string $name;
    public ?string $cmt;
    public ?string $desc;
    public ?string $src;
    public array $links;
    public ?string $sym;
    public ?string $type;
    private ?FixType $fix;
    public ?int $sat;
    public ?float $hdop;
    public ?float $vdop;
    public ?float $pdop;
    public ?float $ageofdgpsdata;
    private ?DgpsStationType $dgpsid;
    public ?ExtensionsType $extensions;

    private string $toStringTagName;

    public function __construct()
    {
        $this->lat = new LatitudeType();
        $this->lon = new LongitudeType();
        $this->ele = null;
        $this->time = null;
        $this->magvar = new DegreesType();
        $this->geoidheight = null;
        $this->name = null;
        $this->cmt = null;
        $this->desc = null;
        $this->src = null;
        $this->links = [];
        $this->sym = null;
        $this->type = null;
        $this->fix = new FixType();
        $this->sat = null;
        $this->hdop = null;
        $this->vdop = null;
        $this->pdop = null;
        $this->ageofdgpsdata = null;
        $this->dgpsid = new DgpsStationType();
        $this->extensions = new ExtensionsType();

        $this->toStringTagName = 'wpt';
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->lat = new LatitudeType($this->readAttributeAsDecimal('lat', $currentNode));
        $this->lon = new LongitudeType($this->readAttributeAsDecimal('lon', $currentNode));
        $this->ele = $this->evaluateDecimal($xPath, './ns:ele', $currentNode);
        $this->time = $this->evaluateDateTime($xPath, './ns:time', $currentNode);

        $this->magvar = new DegreesType($this->evaluateDecimal($xPath, './ns:magvar', $currentNode));

        $this->geoidheight = $this->evaluateDecimal($xPath, './ns:geoidheight', $currentNode);

        $this->name = $this->evaluateString($xPath, './ns:name', $currentNode);
        $this->cmt = $this->evaluateString($xPath, './ns:cmt', $currentNode);
        $this->desc = $this->evaluateString($xPath, './ns:desc', $currentNode);
        $this->src = $this->evaluateString($xPath, './ns:src', $currentNode);

        $this->links = [];

        $nodes = $xPath->query('./ns:link', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->links[] = (new LinkType())->loadFromXpath($xPath, $nodes[$i]);
        }

        $this->sym = $this->evaluateString($xPath, './ns:sym', $currentNode);
        $this->type = $this->evaluateString($xPath, './ns:type', $currentNode);

        $this->fix = new FixType($this->evaluateString($xPath, './ns:fix', $currentNode));

        $this->sat = $this->evaluateNotNegativeInteger($xPath, './ns:sat', $currentNode);

        $this->hdop = $this->evaluateDecimal($xPath, './ns:hdop', $currentNode);
        $this->vdop = $this->evaluateDecimal($xPath, '/ns:vdop', $currentNode);
        $this->pdop = $this->evaluateDecimal($xPath, './ns:pdop', $currentNode);
        $this->ageofdgpsdata = $this->evaluateDecimal($xPath, './ns:ageofdgpsdata', $currentNode);

        $this->dgpsid = new DgpsStationType($this->evaluateInteger($xPath, './ns:dgpsid', $currentNode));

        $nodes = $xPath->query('./ns:extensions', $currentNode);
        $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($xPath, $nodes[0]) : null;
    
        return $this;
    }

    public function setToStringTagName(string $tagName = 'wpt')
    {
        $this->toStringTagName = $tagName;
    }

    public function __toString() : string
    {
        $string = '<' . $this->toStringTagName . ' ';

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

        if (!is_null($this->magvar->value)) {
            $string .= '<magvar>' . $this->magvar . '</magvar>';
        }

        if (!is_null($this->geoidheight)) {
            $string .= '<geoidheight>' . $this->geoidheight . '</geoidheight>';
        }

        if (!is_null($this->name)) {
            $string .= '<name>' . $this->name . '</name>';
        }

        if (!is_null($this->cmt)) {
            $string .= '<cmt>' . $this->cmt . '</cmt>';
        }

        if (!is_null($this->desc)) {
            $string .= '<desc>' . $this->desc . '</desc>';
        }

        if (!is_null($this->src)) {
            $string .= '<src>' . $this->src . '</src>';
        }

        foreach ($this->links as $link) {
            $string .= $link;
        }

        if (!is_null($this->sym)) {
            $string .= '<sym>' . $this->sym . '</sym>';
        }

        if (!is_null($this->type)) {
            $string .= '<type>' . $this->type . '</type>';
        }

        if (!is_null($this->fix->value)) {
            $string .= '<fix>' . $this->fix . '</fix>';
        }

        if (!is_null($this->sat)) {
            $string .= '<sat>' . $this->sat . '</sat>';
        }

        if (!is_null($this->hdop)) {
            $string .= '<hdop>' . $this->hdop . '</hdop>';
        }

        if (!is_null($this->vdop)) {
            $string .= '<vdop>' . $this->vdop . '</vdop>';
        }

        if (!is_null($this->pdop)) {
            $string .= '<pdop>' . $this->pdop . '</pdop>';
        }

        if (!is_null($this->ageofdgpsdata)) {
            $string .= '<ageofdgpsdata>' . $this->ageofdgpsdata . '</ageofdgpsdata>';
        }

        if (!is_null($this->dgpsid->value)) {
            $string .= '<dgpsid>' . $this->dgpsid . '</dgpsid>';
        }

        $string .= $this->extensions;

        $string .= '</' . $this->toStringTagName . '>';

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

            case 'magvar':
                return $this->magvar->value;
                break;

            case 'fix':
                return $this->fix->value;
                break;

            case 'dgpsid':
                return $this->dgpsid->value;
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

            case 'magvar':
                $this->magvar->value = $value;
                break;

            case 'fix':
                $this->fix->value = $value;
                break;

            case 'dgpsid':
                $this->dgpsid->value = $value;
                break;

            default:
                throw new ErrorException(__METHOD__ . ': invalid __set() name (' . $name . ')');
                break;
        }
    }
}
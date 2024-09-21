<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class TrksegType extends BaseType
{
    public array $trkpt;
    public ?ExtensionsType $extensions;

    public function __construct()
    {
        $this->trkpt = [];
        $this->extensions = new ExtensionsType();
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->trkpt = [];

        $nodes = $xPath->query('./ns:trkpt', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->trkpt[] = (new WptType())->loadFromXpath($xPath, $nodes[$i]);
        }

        $nodes = $xPath->query('./ns:extensions', $currentNode);
        $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($xPath, $nodes[0]) : null;

        return $this;
    }

    public function __toString() : string
    {
        $string = '<trkseg>';

        foreach ($this->trkpt as $trkpt) {
            $trkpt->setToStringTagName('trkpt');
            $string .= $trkpt;
        }

        $string .= $this->extensions;

        $string .= '</trkseg>';

        return $string;
    }
}
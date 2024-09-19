<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class PtsegType extends BaseType
{
    public array $pt;

    public function __construct()
    {
        $this->pt = [];
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $nodes = $xPath->query('./ns:pt', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->pt[] = (new PtType())->loadFromXpath($xPath, $nodes[$i]);
        }

        return $this;
    }

    public function __toString() : string
    {
        $string = '<ptseg>';

        foreach ($this->pt as $pt) {
            $string .= $pt;
        }

        $string .= '</ptseg>';

        return $string;
    }
}
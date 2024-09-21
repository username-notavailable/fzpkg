<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class MetadataType extends BaseType
{
    public ?string $name;
    public ?string $desc;
    public ?PersonType $author;
    public ?CopyrightType $copyright;
    public array $links;
    public ?string $time;
    public ?string $keywords;
    public ?BoundsType $bounds;
    public ?ExtensionsType $extensions;

    public function __construct()
    {
        $this->name = null;
        $this->desc = null;
        $this->author = new PersonType();
        $this->copyright = new CopyrightType();
        $this->links = [];
        $this->time = null;
        $this->keywords = null;
        $this->bounds = new BoundsType();
        $this->extensions = new ExtensionsType();
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->name = $this->evaluateString($xPath, './ns:name', $currentNode);
        $this->desc = $this->evaluateString($xPath, './ns:desc', $currentNode);

        $nodes = $xPath->query('./ns:author', $currentNode);
        $this->author = $nodes->count() > 0 ? (new PersonType())->loadFromXpath($xPath, $nodes[0]) : null;

        $nodes = $xPath->query('./ns:copyright', $currentNode);
        $this->copyright = $nodes->count() > 0 ? (new CopyrightType())->loadFromXpath($xPath, $nodes[0]) : null;

        $this->links = [];

        $nodes = $xPath->query('./ns:link', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->links[] = (new LinkType())->loadFromXpath($xPath, $nodes[$i]);
        }

        $this->time = $this->evaluateDateTime($xPath, './ns:time', $currentNode);
        $this->keywords = $this->evaluateString($xPath, './ns:keywords', $currentNode);

        $nodes = $xPath->query('./ns:bounds', $currentNode);
        $this->bounds = $nodes->count() > 0 ? (new BoundsType())->loadFromXpath($xPath, $nodes[0]) : null;

        $nodes = $xPath->query('./ns:extensions', $currentNode);
        $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($xPath, $nodes[0]) : null;

        return $this;
    }

    public function __toString() : string
    {
        $string = '<metadata>';

        if (!is_null($this->name)) {
            $string .= '<name>' . $this->name . '</name>';
        }

        if (!is_null($this->desc)) {
            $string .= '<desc>' . $this->desc . '</desc>';
        }

        $string .= $this->author;
        $string .= $this->copyright;

        foreach ($this->links as $link) {
            $string .= $link;
        }

        if (!is_null($this->time)) {
            $string .= '<time>' . $this->time . '</time>';
        }

        if (!is_null($this->keywords)) {
            $string .= '<keywords>' . $this->keywords . '</keywords>';
        }

        $string .= $this->bounds;

        $string .= $this->extensions;

        $string .= '</metadata>';

        return $string;
    }
}
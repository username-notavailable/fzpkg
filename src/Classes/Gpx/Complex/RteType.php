<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class RteType extends BaseType
{
    public ?string $name;
    public ?string $cmt;
    public ?string $desc;
    public ?string $src;
    public array $links;
    public ?int $number;
    public ?string $type;
    public ?ExtensionsType $extensions;
    public array $rtept;
    
    public function __construct()
    {
        $this->name = null;
        $this->cmt = null;
        $this->desc = null;
        $this->src = null;
        $this->links = [];
        $this->number = null;
        $this->type = null;
        $this->extensions = new ExtensionsType();
        $this->rtept = [];
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->name = $this->evaluateString($xPath, './ns:name', $currentNode);
        $this->cmt = $this->evaluateString($xPath, './ns:cmt', $currentNode);
        $this->desc = $this->evaluateString($xPath, './ns:desc', $currentNode);
        $this->src = $this->evaluateString($xPath, './ns:src', $currentNode);

        $this->links = [];

        $nodes = $xPath->query('./ns:link', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->links[] = (new LinkType())->loadFromXpath($xPath, $nodes[$i]);
        }

        $this->number = $this->evaluateNotNegativeInteger($xPath, './ns:number', $currentNode);
        $this->type = $this->evaluateString($xPath, './ns:type', $currentNode);

        $nodes = $xPath->query('./ns:extensions', $currentNode);
        $this->extensions = $nodes->count() > 0 ? (new ExtensionsType())->loadFromXpath($xPath, $nodes[0]) : null;

        $this->rtept = [];

        $nodes = $xPath->query('./ns:rtept', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->rtept[] = (new WptType())->loadFromXpath($xPath, $nodes[$i]);
        }

        return $this;
    }

    public function __toString() : string
    {
        $string = '<rte>';

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

        if (!is_null($this->number)) {
            $string .= '<number>' . $this->number . '</number>';
        }

        if (!is_null($this->type)) {
            $string .= '<type>' . $this->type . '</type>';
        }

        foreach ($this->rtept as $rtept) {
            $rtept->setToStringTagName('rtept');
            $string .= $rtept;
        }

        $string .= '</rte>';

        return $string;
    }
}
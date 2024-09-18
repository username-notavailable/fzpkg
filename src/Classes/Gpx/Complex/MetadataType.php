<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class MetadataType
{
    public string $name;
    public string $desc;
    public ?PersonType $author;
    public ?CopyrightType $copyright;
    public array $links;
    public string $time;
    public string $keywords;
    public ?BoundsType $bounds;
    public ?\DOMNode $extensions;

    public function __construct(\DOMXPath &$xPath, \DOMNode &$currentNode)
    {
        $this->name = $xPath->evaluate('string(./ns:name)', $currentNode);
        $this->desc = $xPath->evaluate('string(./ns:desc)', $currentNode);

        $nodes = $xPath->query('./ns:author', $currentNode);
        $this->author = $nodes->count() > 0 ? new PersonType($xPath, $nodes[0]) : null;

        $nodes = $xPath->query('./ns:copyright', $currentNode);
        $this->copyright = $nodes->count() > 0 ? new CopyrightType($xPath, $nodes[0]) : null;

        $this->links = [];

        $nodes = $xPath->query('./ns:link', $currentNode);
        for ($i = 0; $i < $nodes->length; $i++) {
            $this->links[] = new LinkType($xPath, $nodes[$i]);
        }

        $this->time = $xPath->evaluate('string(./ns:time)', $currentNode);
        $this->keywords = $xPath->evaluate('string(./ns:keywords)', $currentNode);

        $nodes = $xPath->query('./ns:bounds', $currentNode);
        $this->bounds = $nodes->count() > 0 ? new BoundsType($xPath, $nodes[0]) : null;

        $nodes = $xPath->query('./ns:extensions', $currentNode);
        $this->extensions = $nodes->count() > 0 ? $nodes[0] : null;
    }
}
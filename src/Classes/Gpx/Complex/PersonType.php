<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class PersonType extends BaseType
{
    public ?string $name;
    public ?EmailType $email;
    public ?LinkType $link;

    public function __construct()
    {
        $this->name = null;
        $this->email = new EmailType();
        $this->link = new LinkType();
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->name = $this->evaluateString($xPath, './ns:name', $currentNode);

        $nodes = $xPath->query('./ns:email', $currentNode);
        $this->email = $nodes->count() > 0 ? (new EmailType())->loadFromXpath($xPath, $nodes[0]) : null;

        $nodes = $xPath->query('./ns:link', $currentNode);
        $this->link = $nodes->count() > 0 ? (new LinkType())->loadFromXpath($xPath, $nodes[0]) : null;

        return $this;
    }
}
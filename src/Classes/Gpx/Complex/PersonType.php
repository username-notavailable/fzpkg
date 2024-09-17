<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class PersonType
{
    public string $name;
    public ?EmailType $email;
    public ?LinkType $link;

    public function __construct(\DOMXPath &$xPath, \DOMNode $currentNode)
    {
        $this->name = $xPath->evaluate('string(./ns:name)', $currentNode);

        $nodes = $xPath->query('./ns:email', $currentNode);
        $this->email = $nodes->count() > 0 ? new EmailType($xPath, $nodes[0]) : null;

        $nodes = $xPath->query('./ns:link', $currentNode);
        $this->link = $nodes->count() > 0 ? new LinkType($xPath, $nodes[0]) : null;
    }
}
<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class EmailType
{
    public string $id;
    public string $domain;

    public function __construct(\DOMXPath &$xPath, \DOMNode $currentNode)
    {
        $this->id = $currentNode->getAttribute('id');
        $this->domain = $currentNode->getAttribute('domain');
    }
}
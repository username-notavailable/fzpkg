<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class LinkType
{
    public string $href;
    public string $text;
    public string $type;

    public function __construct(\DOMXPath &$xPath, \DOMNode $currentNode)
    {
        $this->href = $currentNode->getAttribute('href');
        $this->text = $xPath->evaluate('string(./ns:text)', $currentNode);
        $this->type = $xPath->evaluate('string(./ns:type)', $currentNode);
    }
}
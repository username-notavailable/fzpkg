<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class CopyrightType
{
    public string $author;
    public string $year;
    public string $license;

    public function __construct(\DOMXPath &$xPath, \DOMNode &$currentNode)
    {
        $this->author = $currentNode->getAttribute('author');
        $this->year = $xPath->evaluate('string(./ns:year)', $currentNode);
        $this->license = $xPath->evaluate('string(./ns:license)', $currentNode);
    }
}
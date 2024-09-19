<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class CopyrightType extends BaseType
{
    public ?string $author;
    public ?string $year;
    public ?string $license;

    public function __construct()
    {
        $this->author = null;
        $this->year = null;
        $this->license = null;
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->author = $this->readAttributeAsString('author', $currentNode);
        $this->year = $this->evaluateString($xPath, './ns:year', $currentNode);
        $this->license = $this->evaluateURI($xPath, './ns:license', $currentNode);

        return $this;
    }
}
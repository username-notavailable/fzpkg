<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class EmailType extends BaseType
{
    public ?string $id;
    public ?string $domain;

    public function __construct()
    {
        $this->id = null;
        $this->domain = null;
    }

    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->id = $this->readAttributeAsString('id', $currentNode);
        $this->domain = $this->readAttributeAsString('domain', $currentNode);

        return $this;
    }
}
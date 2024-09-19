<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class ExtensionsType
{
    protected ?\DOMXPath $xPath;
    protected ?\DOMNode $currentNode;

    public function __construct()
    {
        $this->xPath = null;
        $this->currentNode = null;
    }

    public function loadFromXpath(\DOMXPath $xPath, \DOMNode $currentNode) : self
    {
        $this->xPath = $xPath;
        $this->currentNode = $currentNode;

        return $this;
    }

    public function query($expression, bool $registerNodeNS = true) : \DOMNodeList
    {
        return $this->xPath->query($expression, $this->currentNode, $registerNodeNS);
    }

    public function evaluate($expression, bool $registerNodeNS = true) : mixed
    {
        return $this->xPath->evaluate($expression, $this->currentNode, $registerNodeNS);
    }

    public function getXpath() : \DOMXPath
    {
        return $this->xPath;
    }

    public function getCurrentNode() : \DOMNode
    {
        return $this->currentNode;
    }
}
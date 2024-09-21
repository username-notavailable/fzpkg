<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

class ExtensionsType
{
    protected ?\DOMXPath $xPath;
    public ?\DOMNode $node;

    public function __construct()
    {
        $this->xPath = null;
        $this->node = null;
    }

    public function loadFromXpath(\DOMXPath $xPath, \DOMNode $node) : self
    {
        $this->xPath = $xPath;
        $this->node = $node;

        return $this;
    }

    public function __toString() : string
    {
        $string = '';

        if (!is_null($this->node)) {
            $string .= $this->__nodeToString($this->node);
        }
        
        return $string;
    }

    private function __nodeToString(\DOMElement $node) : string
    {
        $string = '<' . $node->nodeName . '>';

        if ($node->childElementCount > 0) {
            for ($i = 0; $i < count($node->childNodes); $i++) {
                if ($node->childNodes[$i]->nodeType === XML_ELEMENT_NODE) {
                    $string .= $this->__nodeToString($node->childNodes[$i]);
                }
            }
        }
        else {
            $string .= $node->nodeValue;
        }
        
        $string .= '</' . $node->nodeName . '>';

        return $string;
    }

    public function query($expression, bool $registerNodeNS = true) : \DOMNodeList
    {
        return $this->xPath->query($expression, $this->node, $registerNodeNS);
    }

    public function evaluate($expression, bool $registerNodeNS = true) : mixed
    {
        return $this->xPath->evaluate($expression, $this->node, $registerNodeNS);
    }
}
<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Complex;

use Fuzzy\Fzpkg\Classes\Gpx\BaseType;

class LinkType extends BaseType
{
    public ?string $href;
    public ?string $text;
    public ?string $type;

    public function __construct()
    {
        $this->href = null;
        $this->text = null;
        $this->type = null;
    }
    
    public function loadFromXpath(\DOMXPath &$xPath, \DOMNode &$currentNode) : self
    {
        $this->href = $this->readAttributeAsURI('href', $currentNode);
        $this->text = $this->evaluateString($xPath, './ns:text', $currentNode);
        $this->type = $this->evaluateString($xPath, './ns:type', $currentNode);

        return $this;
    }

    public function __toString() : string
    {
        $string = '<link ';

        if (!is_null($this->href)) {
            $string .= 'href="' . $this->href . '" ';
        }

        $string = trim($string) . '>';

        if (!is_null($this->text)) {
            $string .= '<text>' . $this->text . '</text>';
        }

        if (!is_null($this->type)) {
            $string .= '<type>' . $this->type . '</type>';
        }

        $string .= '</link>';

        return $string;
    }
}
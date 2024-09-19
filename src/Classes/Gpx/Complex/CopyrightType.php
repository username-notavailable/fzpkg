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

    public function __toString() : string
    {
        $string = '<copyright ';

        if (!is_null($this->author)) {
            $string .= 'author="' . $this->author . '" ';
        }

        $string = trim($string) . '>';

        if (!is_null($this->year)) {
            $string .= '<year>' . $this->year . '</year>';
        }

        if (!is_null($this->license)) {
            $string .= '<license>' . $this->license . '</license>';
        }

        $string .= '</copyright>';

        return $string;
    }
}
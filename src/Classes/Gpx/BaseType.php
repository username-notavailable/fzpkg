<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx;

class BaseType
{
    protected function readAttribute(string $attributeName, \DOMNode &$currentNode) : mixed
    {
        if ($currentNode->hasAttributes()) {
            for ($i = 0; $i < $currentNode->attributes->length; $i++) {
                if ($currentNode->attributes->item($i)->name === $attributeName) {
                    return $currentNode->attributes->item($i)->value;
                }
            }
        }

        return null;
    }

    protected function readAttributeAsString(string $attributeName, \DOMNode &$currentNode) : mixed
    {
        return $this->readAttribute($attributeName, $currentNode);
    }

    protected function readAttributeAsURI(string $attributeName, \DOMNode &$currentNode) : mixed
    {
        $data = $this->readAttribute($attributeName, $currentNode);

        if (!is_null($data) && !filter_var($data, FILTER_VALIDATE_URL)) {
            $data = null;
        }

        return $data;
    }

    protected function readAttributeAsDecimal(string $attributeName, \DOMNode &$currentNode) : mixed
    {
        $data = $this->readAttribute($attributeName, $currentNode);

        if (!is_null($data)) {
            if (!filter_var($data, FILTER_VALIDATE_FLOAT)) {
                $data = null;
            }
            else {
                $data = floatval($data);

                if (is_nan($data)) {
                    $data = null;
                }
            }            
        }

        return $data;
    }

    protected function evaluateNodeExists(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        $nodes = $xPath->query($expression, $currentNode);

        return $nodes->count() > 0 ? $nodes[0]->nodeValue : null;
    }

    protected function evaluateString(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        return $this->evaluateNodeExists($xPath, $expression, $currentNode);
    }

    protected function evaluateDateTime(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        $data = $this->evaluateNodeExists($xPath, $expression, $currentNode);

        if (!is_null($data)) {
            try {
                $date = date_parse_from_format(DATE_ATOM, $data);

                if ($date['warning_count'] > 0 || $date['error_count']) {
                    $data = null;
                }
            }
            catch(\ValueError $e) {
                $data = null;
            }
        }
        
        return $data;
    }

    protected function evaluateURI(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        $data = $this->evaluateNodeExists($xPath, $expression, $currentNode);

        if (!is_null($data) && !filter_var($data, FILTER_VALIDATE_URL)) {
            $data = null;
        }
        
        return $data;
    }

    protected function evaluateDecimal(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        $data = $this->evaluateNodeExists($xPath, $expression, $currentNode);

        if (!is_null($data)) {
            if (!filter_var($data, FILTER_VALIDATE_FLOAT)) {
                $data = null;
            }
            else {
                $data = floatval($data);

                if (is_nan($data)) {
                    $data = null;
                }
            }            
        }
        
        return $data;
    }

    protected function evaluateInteger(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        $data = $this->evaluateNodeExists($xPath, $expression, $currentNode);

        if (!is_null($data)) {
            if (!filter_var($data, FILTER_VALIDATE_INT)) {
                $data = null;
            }
            else {
                $data = intval($data);
            }            
        }
        
        return $data;
    }

    protected function evaluateNotNegativeInteger(\DOMXPath &$xPath, string $expression, \DOMNode &$currentNode) : mixed
    {
        $data = $this->evaluateNodeExists($xPath, $expression, $currentNode);

        if (!is_null($data)) {
            if (!filter_var($data, FILTER_VALIDATE_INT)) {
                $data = null;
            }
            else {
                $data = intval($data);

                if ($data < 0) {
                    $data = null;
                }
            }            
        }
        
        return $data;
    }
}
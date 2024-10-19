<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route 
{
    public function __construct(public string $path = '', public string $verbs = '*', public string $name = '', public array $where = [], public array $whereNumber = [], public array $whereAlpha = [], public array $whereAlphaNumeric = [], public array $whereUuid = [], public array $whereUlid = [], public array $whereIn = [], public bool $withTrashed = false, public ?bool $scopeBindings = null, public array $consumes = [], public string $summary = '', public string $description = '', public bool $deprecated = false)
    {
        $verbs = explode('|', $this->verbs);

        foreach($verbs as $verb) {
            if (!in_array(strtolower($verb), ['*', 'get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'])) {
                throw new InvalidArgumentException('Invalid verb "' . $verb . '"');
            }
        }
    }

    public function resolveWhereX() : string
    {
        $result = '';

        if (!empty($this->where)) {
            $items = [];

            foreach ($this->where as $key => $value) {
                $items[] = "'$key' => '$value'";
            }

            $result .= '->where([' . implode(',', $items) . '])';
        }

        if (!empty($this->whereNumber)) {
            foreach ($this->whereNumber as $key) {
                $result .= "->whereNumber('$key')";
            }
        }

        if (!empty($this->whereAlpha)) {
            foreach ($this->whereAlpha as $key) {
                $result .= "->whereAlpha('$key')";
            }
        }

        if (!empty($this->whereAlphaNumeric)) {
            foreach ($this->whereAlphaNumeric as $key) {
                $result .= "->whereAlphaNumeric('$key')";
            }
        }

        if (!empty($this->whereUuid)) {
            foreach ($this->whereUuid as $key) {
                $result .= "->whereUuid('$key')";
            }
        }

        if (!empty($this->whereUlid)) {
            foreach ($this->whereUlid as $key) {
                $result .= "->whereUlid('$key')";
            }
        }

        if (!empty($this->whereIn)) {
            foreach ($this->whereIn as $key => $values) {
                if (!is_array($values)) {
                    throw new InvalidArgumentException('Invalid values in whereIn for param "' . $key . '"');
                }

                $items = [];

                foreach ($values as $value) {
                    $items[] = "'$value'";
                }

                $result .= "->whereIn('$key', [" . implode(',', $items) . "])";
            }
        }

        return $result;
    }
}

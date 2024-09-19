<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Simple;

use ErrorException;

class FixType
{
    private ?string $value;

    public function __construct(?string $value = null)
    {
        $this->__set('value', $value);
    }

    public function __get(string $name) : mixed
    {
        if ($name === 'value') {
            return $this->value;
        }

        throw new ErrorException(__METHOD__ . ': invalid __get() name (' . $name . ')');
    }

    public function __set(string $name, ?string $value) : void
    {
        if ($name === 'value') {
            $this->value = $value;
        }
        else {
            throw new ErrorException(__METHOD__ . ': invalid __set() name (' . $name . ')');
        }
    }
}
<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Gpx\Simple;

use ErrorException;
use Illuminate\Support\Facades\Log;

class LatitudeType
{
    private ?float $value;

    public function __construct(?float $value = null)
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

    public function __set(string $name, ?float $value) : void
    {
        if ($name === 'value') {
            if (!is_null($value) && ($value < -90 || $value > 90)) {
                Log::error(__METHOD__ . ': invalid __set() value (' . $value . ')');
                $this->value = null;
            }
            else {
                $this->value = $value;
            }
        }
        else {
            throw new ErrorException(__METHOD__ . ': invalid __set() name (' . $name . ')');
        }
    }
}
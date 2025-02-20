<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class WithParam 
{
    public function __construct(public array $schemaParams = [])
    {
        foreach (['name', 'in'] as $paramName) {
            if (!array_key_exists($paramName, $this->schemaParams)) {
                throw new InvalidArgumentException(__METHOD__ .': Param "' . $paramName . '" is required');
            }
        }
        
        if (!in_array($this->schemaParams['in'], ['query', 'header', 'path', 'formData', 'body', 'cookie'])) {
            throw new InvalidArgumentException(__METHOD__ . ': Invalid "in" value (' . $this->schemaParams['in'] . ')');
        }

        if ($this->schemaParams['in'] === 'body') {
            if (!array_key_exists('schema', $this->schemaParams)) {
                throw new InvalidArgumentException(__METHOD__ . ': If "in" === "body" param "schema" is required');
            }
        }
        else {
            if (!array_key_exists('type', $this->schemaParams)) {
                throw new InvalidArgumentException(__METHOD__ . ': If "in" !== "body" param "type" is required');
            }
            else {
                if ($this->schemaParams['in'] === 'path') {
                    $this->schemaParams['required'] = true;
                }

                if (!in_array($this->schemaParams['type'], ['string', 'number', 'integer', 'boolean', 'array', 'file'])) {
                    throw new InvalidArgumentException(__METHOD__ . ': If "in" !== "body" param "type" must be one of ["string", "number", "integer", "boolean", "array", "file"]');
                }

                if ($this->schemaParams['type'] === 'file' && $this->schemaParams['in'] !== 'formData') {
                    throw new InvalidArgumentException(__METHOD__ . ': If "type" === "file" param "in" must be "formData"');
                }

                if ($this->schemaParams['type'] === 'array' && !array_key_exists('items', $this->schemaParams)) {
                    throw new InvalidArgumentException(__METHOD__ . ': If "type" === "array" param "items" must be specified');
                }
            }
        }
    }
}
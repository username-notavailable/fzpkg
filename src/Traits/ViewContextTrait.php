<?php

namespace Fuzzy\Fzpkg\Traits;

trait ViewContextTrait
{
    public array $viewContext = [];

    public function initFromViewContext(array $viewContext, string $contextName)
    {
        foreach ((new \ReflectionObject($this))->getProperties() as $property) {
            $propertyName = $property->getName();

                if (isset($viewContext[$contextName][$propertyName])) {
                    $property->setValue($this, $viewContext[$contextName][$propertyName]);
                }
        }

        $this->viewContext = $viewContext;
    }
}
<?php

namespace Fuzzy\Fzpkg\Traits;

trait JsonObjectTrait
{
    public function getJsonObject() : string|false
    {
        $object = new \ReflectionObject($this);
        $jsonObject = [];

        foreach ($object->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $jsonObject[$property->getName()] = $property->getValue($this);
        }

        return json_encode($jsonObject, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);
    }

    /*protected function restoreState() : void
    {
        $object = new \ReflectionObject($this);

        foreach ($this->__trait_objectState__ as $propertyName => $propertyValue) {
            $property = $object->getProperty($propertyName);
            $property->setAccessible(true);
            
            $property->setValue($this, $propertyValue);
        }
    }

    protected function flushStoredState() : void
    {
        $this->__trait_objectState__ = [];
    }

    protected function getStoredState() : array
    {
        return $this->__trait_objectState__;
    }*/
}
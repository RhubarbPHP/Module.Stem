<?php

namespace Rhubarb\Stem\Custard\CommandHelpers;

class GetterOrSetterMethod
{
    /** @var \ReflectionMethod */
    protected $method;
    /** @var string */
    protected $propertyName;

    protected $readable = false;
    protected $writable = false;

    /**
     * @param \ReflectionMethod $method
     * @param self[] $existingMethods
     * @return bool|self
     */
    public static function fromReflectionMethod(\ReflectionMethod $method, $existingMethods)
    {
        $wrapper = new self();
        $wrapper->method = $method;

        $methodName = $method->getName();

        if (stripos($methodName, 'get') === 0) {
            $wrapper->readable = true;
        } elseif (stripos($methodName, 'set') === 0) {
            $wrapper->writable = true;
        } else {
            // Neither a getter nor a setter
            return false;
        }

        $wrapper->propertyName = substr($methodName, 3);

        if (isset($existingMethods[$wrapper->propertyName])) {
            $existingMethods[$wrapper->propertyName]->readable |= $wrapper->readable;
            $existingMethods[$wrapper->propertyName]->writable |= $wrapper->writable;

            // Getter or setter for the same name already found
            return false;
        }

        return $wrapper;
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function getTagName()
    {
        if ($this->readable && $this->writable) {
            return 'property';
        } elseif ($this->writable) {
            return 'property-write';
        } else {
            return 'property-read';
        }
    }

    public function getReflectionMethod()
    {
        return $this->method;
    }
}

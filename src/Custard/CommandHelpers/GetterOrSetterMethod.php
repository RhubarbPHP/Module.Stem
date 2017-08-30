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
            $parameters = $method->getParameters();

            $wrapper->readable = true;
            foreach ($parameters as $parameter) {
                if (!$parameter->allowsNull()) {
                    // If a "get" method has any non-nullable parameters, it's not a property getter
                    $wrapper->readable = false;
                    break;
                }
            }

            if (!$wrapper->readable) {
                return false;
            }
        } elseif (stripos($methodName, 'set') === 0) {
            $parameters = $method->getParameters();
            $paramCount = count($parameters);

            if ($paramCount > 0) {
                // A "set" method must take a parameter to be a property setter
                $wrapper->writable = true;

                for ($i = 1; $i < $paramCount; $i++) {
                    if (!$parameter->allowsNull()) {
                        // If a "set" method has more than 1 non-nullable parameter, it's not a property setter
                        $wrapper->writable = false;
                        break;
                    }
                }
            }

            if (!$wrapper->writable) {
                return false;
            }
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

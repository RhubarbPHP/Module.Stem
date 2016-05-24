<?php

namespace Rhubarb\Stem\Collections;

class Intersection
{
    public $collection;
    public $parentColumnName;
    public $childColumnName;

    public function __construct($collection, $parentColumnName, $childColumnName)
    {
        $this->collection = $collection;
        $this->parentColumnName = $parentColumnName;
        $this->childColumnName = $childColumnName;
    }
}
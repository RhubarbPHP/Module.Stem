<?php

namespace Rhubarb\Stem\Collections;

class Intersection
{
    public $collection;

    public $parentColumnName;

    public $childColumnName;
    
    public $columnsToPullUp = [];

    /**
     * True if the intersection has already happened.
     *
     * @var bool
     */
    public $intersected = false;

    public function __construct($collection, $parentColumnName, $childColumnName, $columnsToPullUp)
    {
        $this->collection = $collection;
        $this->parentColumnName = $parentColumnName;
        $this->childColumnName = $childColumnName;
        $this->columnsToPullUp = $columnsToPullUp;
    }
}
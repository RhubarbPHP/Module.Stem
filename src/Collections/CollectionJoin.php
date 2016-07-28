<?php

namespace Rhubarb\Stem\Collections;

class CollectionJoin
{
    const JOIN_TYPE_INTERSECTION = "Intersection";
    const JOIN_TYPE_ATTACH = "Attach";

    public $collection;

    public $sourceColumnName;

    public $targetColumnName;

    public $columnsToPullUp = [];

    public $joinType;

    /**
     * True if the intersection has already happened.
     *
     * @var bool
     */
    public $intersected = false;

    public $autoHydrate = false;

    public function __construct($collection, $sourceColumnName, $targetColumnName, $columnsToPullUp, $autoHydrate, $joinType)
    {
        $this->collection = $collection;
        $this->sourceColumnName = $sourceColumnName;
        $this->targetColumnName = $targetColumnName;
        $this->columnsToPullUp = $columnsToPullUp;
        $this->autoHydrate = $autoHydrate;
        $this->joinType = $joinType;
    }
}
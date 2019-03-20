<?php

namespace Rhubarb\Stem\Collections;

class ArrayCollection extends Collection
{
    private $modelArray = [];

    public function __construct($schema, $modelArray = [])
    {
        parent::__construct($schema);

        $this->modelArray = $modelArray;
    }

    protected function createCursor()
    {
        return new ModelListCursor($this->modelArray);
    }
}
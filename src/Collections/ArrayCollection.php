<?php

namespace Rhubarb\Stem\Collections;

class ArrayCollection extends Collection
{
    private $modelArray = [];

    public function __construct($modelClassName, $modelArray = [])
    {
        parent::__construct($modelClassName);

        $this->modelArray = $modelArray;
    }

    protected function createCursor()
    {
        return new ModelListCursor($this->modelArray);
    }
}
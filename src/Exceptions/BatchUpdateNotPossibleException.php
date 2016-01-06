<?php

namespace Rhubarb\Stem\Exceptions;

use Rhubarb\Crown\Exceptions\RhubarbException;

class BatchUpdateNotPossibleException extends RhubarbException
{
    public function __construct()
    {
        parent::__construct("Batch updating this collection is not possible");
    }
}

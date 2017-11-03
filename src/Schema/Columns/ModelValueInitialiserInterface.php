<?php

namespace Rhubarb\Stem\Schema\Columns;

use Rhubarb\Stem\Models\Model;

interface ModelValueInitialiserInterface
{
    public function onNewModelInitialising(Model $model);
}

<?php

namespace Rhubarb\Stem\Schema\Columns;

use Rhubarb\Stem\Models\Model;

interface ModelValueInitialiserInterface
{
    function onNewModelInitialising(Model $model);
}

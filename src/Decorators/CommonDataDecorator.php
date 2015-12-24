<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Stem\Decorators;

require_once __DIR__ . '/DataDecorator.php';

use Rhubarb\Leaf\Presenters\Controls\DateTime\Date;
use Rhubarb\Stem\Decorators\Formatters\DecimalFormatter;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DecimalColumn;
use Rhubarb\Stem\Schema\Columns\MoneyColumn;

/**
 * Provides the most common of decorations to ensure basic conversions are implemented.
 */
class CommonDataDecorator extends DataDecorator
{
    protected function registerTypeDefinitions()
    {
        $this->addTypeFormatter(BooleanColumn::class, function (Model $model, $booleanValue) {
            return $booleanValue ? "Yes" : "No";
        });

        $this->addTypeFormatter(MoneyColumn::class, function (Model $model, $value) {
            return number_format($value, 2);
        });

        $this->addTypeFormatter(DecimalColumn::class, new DecimalFormatter());

        $this->addTypeFormatter(Date::class, function (Model $model, \DateTime $value) {
            return $value->format("d-M-Y");
        });
    }
}
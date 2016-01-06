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

namespace Rhubarb\Stem\Filters;

require_once __DIR__ . '/Group.php';

class Between extends Group
{
    private $min;

    private $max;

    public function __construct($columnName, $min, $max)
    {
        $this->min = $min;
        $this->max = $max;

        parent::__construct(
            "And",
            [
                new GreaterThan($columnName, $min, true),
                new LessThan($columnName, $max, true)
            ]
        );
    }

    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["min"] = $this->min;
        $settings["max"] = $this->max;
        return $settings;
    }

    public static function fromSettingsArray($settings)
    {
        return new self($settings["columnName"], $settings["min"], $settings["max"]);
    }

}
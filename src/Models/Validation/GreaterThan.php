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

namespace Rhubarb\Stem\Models\Validation;

class GreaterThan extends Validation
{
    protected $greaterThan;
    protected $equalTo;

    public function __construct($name, $greaterThan, $equalTo = false)
    {
        parent::__construct($name);

        $this->greaterThan = $greaterThan;
        $this->equalTo = $equalTo;
    }

    public function test($value, $model = null)
    {
        if ($this->equalTo) {
            return $value >= $this->greaterThan;
        }

        return $value > $this->greaterThan;
    }

    public function getDefaultFailedMessage()
    {
        return $this->label . ' must be greater than ' . $this->greaterThan;
    }
}

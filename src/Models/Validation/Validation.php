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

use Rhubarb\Crown\String\StringTools;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;

abstract class Validation
{
    private $inverted = false;

    public $name = "";

    public $label = "";

    /**
     * Set to force the failed message to a particular string, ignoring the default behaviour of the
     * class in the GetFailedMessage() method.
     *
     * @var string
     */
    public $failedMessageOverride = "";

    public function __construct($name)
    {
        $this->name = $name;

        $this->label = StringTools::wordifyStringByUpperCase($name);
    }

    /**
     * Returns a version of this validation the returns the opposite value.
     *
     * Leaves this instance untouched.
     *
     * @return Validation
     */
    final public function invert()
    {
        $clone = clone $this;
        $clone->inverted = true;

        return $clone;
    }

    public function get()
    {

    }

    protected function test($value, $model = null)
    {
    }

    protected function getDefaultFailedMessage()
    {
        return $this->label . " is not valid";
    }

    final public function getFailedMessage()
    {
        return ($this->failedMessageOverride != "") ? $this->failedMessageOverride : $this->getDefaultFailedMessage();
    }

    public function validate($model)
    {
        $value = $model[$this->name];
        $truth = $this->test($value, $model);
        $truth = ($this->inverted) ? !$truth : $truth;

        if (!$truth) {
            throw new ModelConsistencyValidationException([$this->name => $this->getFailedMessage()]);
        }

        return true;
    }
}

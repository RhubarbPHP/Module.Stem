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

require_once __DIR__ . "/Validation.php";

use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;

class Validator extends Validation
{
    const VALIDATE_ALL = 1;
    const VALIDATE_ONE = 2;

    protected $_mode;

    public function __construct($name = "", $mode = self::VALIDATE_ALL)
    {
        parent::__construct($name);

        $this->_mode = $mode;
    }

    /**
     * @var Validation[]
     */
    public $validations = [];

    public function validate($model)
    {
        $exceptions = [];

        foreach ($this->validations as $validation) {
            try {
                $validation->validate($model);
            } catch (ModelConsistencyValidationException $er) {
                $exceptions[$validation->name] = $validation->getFailedMessage();
            }
        }

        if ($this->_mode == self::VALIDATE_ALL) {
            if (sizeof($exceptions) > 0) {
                throw new ModelConsistencyValidationException($exceptions);
            }
        } else {
            if (sizeof($exceptions) == sizeof($this->validations)) {
                throw new ModelConsistencyValidationException($exceptions);
            }
        }

        return true;
    }
}
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

namespace Rhubarb\Stem\Exceptions;

use Rhubarb\Crown\Exceptions\RhubarbException;

/**
 *
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
class ModelException extends RhubarbException
{
    /**
     * The object associated with the data exception.
     *
     * @var
     */
    public $object;

    public function __construct($message, $object)
    {
        $this->object = $object;

        parent::__construct($message);
    }
}

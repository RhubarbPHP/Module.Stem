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

/**
 * A simple data structure to contain the errors thrown by validation.
 */
class ValidationError
{
    /**
     * The name of the validation that err'd
     *
     * @var
     */
    public $name;

    /**
     * A message that the reporter of errors can use (if they want to)
     *
     * @var
     */
    public $message;

    /**
     * A collection
     *
     * @var array
     */
    public $subErrors = [];

    public function __construct($name = "", $message = "")
    {
        $this->name = $name;
        $this->message = $message;
    }

    private function getValidationErrorsByNameRecursive($name, $errorsArray)
    {
        $errors = [];

        foreach ($errorsArray as $error) {
            if ($error->name == $name) {
                $errors[] = $error;
            }

            $errors = array_merge($errors, $this->getValidationErrorsByNameRecursive($name, $error->subErrors));
        }

        return $errors;
    }

    /**
     * Returns an array of validation errors for the validation name provided.
     *
     * Searches within sub errors as well.
     *
     * @param  $name
     * @return array
     */
    public function getValidationErrorsByName($name)
    {
        $errors = [];

        if ($this->name == $name) {
            $errors[] = $this;
        }

        $errors = array_merge($errors, $this->getValidationErrorsByNameRecursive($name, $this->subErrors));

        return $errors;
    }
}
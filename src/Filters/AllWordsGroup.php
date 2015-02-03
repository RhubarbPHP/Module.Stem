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

/**
 * This filter will take a phrase, split its words up, and ensure all words from the phrase are found somewhere in the specified
 * set of fields. This is useful for, for example, doing a full name search between a forename and surname field.
 */
class AllWordsGroup extends Group
{
    /**
     * @param string[] $fieldNames An array of field names
     * @param string|string[] $words An array of words or a string of whitespace or comma separated words
     */
    public function __construct($fieldNames, $words)
    {
        if (!is_array($words)) {
            $words = preg_split('/[\s,]+/', $words);
        }
        $groups = [];
        foreach ($words as $word) {
            $filters = [];
            foreach ($fieldNames as $fieldName) {
                $filters[] = new Contains($fieldName, $word);
            }
            $groups[] = new OrGroup($filters);
        }
        parent::__construct("AND", $groups);
    }
}
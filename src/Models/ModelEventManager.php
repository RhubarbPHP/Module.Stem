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

namespace Rhubarb\Stem\Models;

use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * Co-ordinates events raised by models
 */
class ModelEventManager
{
    public static function dispatchModelEvent($event, Model $model)
    {
        $event = SolutionSchema::getModelNameFromClass(get_class($model)) . ":" . $event;

        if (!isset(self::$eventHandlers[$event])) {
            return null;
        }

        $args = func_get_args();
        $args = array_slice($args, 1);

        // Check if the last argument is a callback.
        $count = count($args);
        $callBack = false;

        if (($count > 0) && is_object($args[$count - 1]) && is_callable($args[$count - 1])) {
            $callBack = $args[$count - 1];
            $args = array_slice($args, 0, -1);
        }

        $result = null;

        foreach (self::$eventHandlers[$event] as $delegate) {
            $answer = call_user_func_array($delegate, $args);

            if ($result === null) {
                $result = $answer;
            }
        }

        if ($callBack !== false) {
            call_user_func($callBack, $result);
        }

        return $result;
    }

    private static $eventHandlers = [];

    public static function attachEventHandler($modelName, $events, callable $delegate)
    {
        if (!is_array($events)) {
            $eventsArray = [$events];
        } else {
            $eventsArray = $events;
        }

        foreach ($eventsArray as $event) {
            if (!isset(self::$eventHandlers[$modelName . ":" . $event])) {
                self::$eventHandlers[$modelName . ":" . $event] = [];
            }

            self::$eventHandlers[$modelName . ":" . $event][] = $delegate;
        }
    }
}
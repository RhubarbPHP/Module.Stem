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

namespace Rhubarb\Stem\Schema\Scripts;

use Rhubarb\Crown\Layout\LayoutModule;
use Rhubarb\Crown\Response\GeneratesResponse;
use Rhubarb\Crown\Response\HtmlResponse;
use Rhubarb\Stem\Schema\SolutionSchema;

class UpdateSchemas implements GeneratesResponse
{
    public function GenerateResponse($request = null)
    {
        if (class_exists("Rhubarb\Crown\Layout\LayoutModule")) {
            LayoutModule::disableLayout();
        }

        $schemas = SolutionSchema::getAllSchemas();

        foreach ($schemas as $schema) {
            $schema->checkModelSchemas();
        }

        $response = new HtmlResponse();
        $response->setContent("Done");

        return $response;
    }
}
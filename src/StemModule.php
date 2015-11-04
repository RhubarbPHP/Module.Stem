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

namespace Rhubarb\Stem;
use Rhubarb\Stem\Custard\DocumentModelsCommand;
use Rhubarb\Stem\Custard\SeedDemoDataCommand;
use Rhubarb\Stem\Custard\UpdateRepositorySchemasCommand;
use Symfony\Component\Console\Command\Command;

/**
 * The Data module provides Active Record objects for PHP.
 *
 * It provides a database agnostic layer with our own particular cherry picks of ORM.
 */
class StemModule extends \Rhubarb\Crown\Module
{
    /**
     * An opportunity for the module to return a list custard command line commands to register.
     *
     * Note that modules are asked for commands in the same order in which the modules themselves
     * were registered. This allows extending modules or scaffolds to superseed a command with an
     * improved version by simply reregistering a command with the same name.
     *
     * @return Command[]
     */
    public function getCustardCommands()
    {
        return
        [
            new DocumentModelsCommand(),
            new UpdateRepositorySchemasCommand(),
            new SeedDemoDataCommand()
        ];
    }

}

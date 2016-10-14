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

namespace Rhubarb\Stem\UrlHandlers;

use Rhubarb\Crown\Exceptions\CollectionUrlException;
use Rhubarb\Crown\UrlHandlers\CollectionUrlHandling;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

abstract class ModelCollectionHandler extends UrlHandler
{
    use CollectionUrlHandling;

    /**
     * The full namespaced class name of our model object.
     *
     * @var
     */
    protected $modelName;

    public function __construct($modelName, $children = [])
    {
        $this->modelName = $modelName;

        parent::__construct($children);
    }

    /**
     * Return's an instance of the model collection.
     *
     * @return \Rhubarb\Stem\Collections\RepositoryCollection
     */
    public function getModelCollection()
    {
        $modelClassName = SolutionSchema::getModelClass($this->modelName);

        return $modelClassName::all();
    }

    public function getModelObject()
    {
        if (!$this->resourceIdentifier) {
            throw new CollectionUrlException();
        }

        return SolutionSchema::getModel($this->modelName, $this->resourceIdentifier);
    }

    protected function populateNewModelWithRelationshipValues(Model $model)
    {
        $schema = SolutionSchema::getModelSchema($this->modelName);

        $model[$schema->uniqueIdentifierColumnName] = $this->resourceIdentifier;

        // If we have a parent handler - see if it can populate our model with some foreign keys.
        $parentHandler = $this->getParentHandler();

        if ($parentHandler !== null && ($parentHandler instanceof ModelCollectionHandler)) {
            $parentHandler->populateNewModelWithRelationshipValues($model);
        }
    }
}

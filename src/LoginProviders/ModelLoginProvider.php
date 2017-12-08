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

namespace Rhubarb\Stem\LoginProviders;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\LoginProviders\Exceptions\NotLoggedInException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 */
class ModelLoginProvider extends LoginProvider
{
    protected $modelClassName = "";

    public $loggedInUserIdentifier = "";

    public function __construct($modelClassName)
    {
        $this->modelClassName = $modelClassName;

        parent::__construct();
    }

    /**
     * Provides a way to set the logged in user based on having the user's model.
     *
     * This is used by things like Api Authentication and makes sure that the means by which permissions are
     * determined is exactly the same as for any other part of the solution.
     *
     * @param  Model $user Required
     * @throws ImplementationException
     */
    public function forceLogin(Model $user = null)
    {
        // The model parameter must be optional to comply with PHP Strict Mode method override rules and as the model
        // is actually required, this ensures that it is provided
        if ($user === null) {
            throw new ImplementationException('A model is required to force login');
        }

        $this->loggedInUserIdentifier = $user->UniqueIdentifier;

        parent::forceLogin();
    }

    /**
     * Returns the model object for the logged in user.
     *
     * @return \Rhubarb\Stem\Models\Model
     * @throws NotLoggedInException
     */
    public function getModel()
    {
        if (!$this->isLoggedIn()) {
            throw new NotLoggedInException();
        }

        if (isset($this->loggedInUserIdentifier)) {
            try {
                return SolutionSchema::getModel($this->modelClassName, $this->loggedInUserIdentifier);
            } catch (RecordNotFoundException $er) {
                throw new NotLoggedInException();
            }
        }

        throw new NotLoggedInException();
    }

    protected function onLogOut()
    {
        // Remove the logged in user identifier from the session.
        unset($this->loggedInUserIdentifier);

        parent::onLogOut();
    }
}

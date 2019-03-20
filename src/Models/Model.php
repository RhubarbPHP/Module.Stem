<?php

/*
 *	Copyright 2019 RhubarbPHP
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

use Gcd\UseCases\Entity;
use Rhubarb\Stem\Collections\Collection;

/**
 * The standard active record implementation in the Core
 *
 * This class serves as a base class for model objects
 *
 * @property string UniqueIdentifierColumnName
 * @property string UniqueIdentifier
 */
abstract class Model implements Entity
{
}

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

namespace Rhubarb\Stem\Repositories\MySql\Aggregates;

use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\SolutionSchema;

trait MySqlAggregateTrait
{
	protected static function canAggregateInMySql( Repository $repository, $columnName, &$relationshipsToAutoHydrate )
	{
		$schema = $repository->getSchema();
		$columns = $schema->getColumns();

		if ( !isset( $columns[ $columnName ] ) )
		{
			if ( strpos( $columnName, "." ) !== false )
			{
				$parts = explode( ".", $columnName, 2 );

				$relationship = $parts[0];
				$relationships = SolutionSchema::getAllRelationshipsForModel( $repository->getModelClass() );

				if ( isset( $relationships[ $relationship ] ) && ( $relationships[ $relationship ] instanceof OneToMany ) )
				{
					$targetModelName = $relationships[ $relationship ]->getTargetModelName();
					$targetSchema = SolutionSchema::getModelSchema( $targetModelName );

					$targetColumns = $targetSchema->getColumns();

					if ( isset( $targetColumns[ $parts[ 1 ] ] ) )
					{
						$relationshipsToAutoHydrate[] = $relationship;
						return true;
					}
				}
			}

			return false;
		}

		return true;
	}
} 
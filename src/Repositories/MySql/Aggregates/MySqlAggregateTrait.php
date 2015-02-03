<?php

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
					$targetModelName = $relationships[ $relationship ]->GetTargetModelName();
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
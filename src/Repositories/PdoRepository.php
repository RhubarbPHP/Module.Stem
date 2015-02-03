<?php

namespace Rhubarb\Stem\Repositories;

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\RepositoryConnectionException;
use Rhubarb\Stem\Exceptions\RepositoryStatementException;
use Rhubarb\Stem\StemSettings;
use Rhubarb\Stem\Models\Model;

/**
 * A base class for PDO based repositories.
 *
 * @package Rhubarb\Stem\Repositories
 * @author      acuthbert
 * @copyright   2013 GCD Technologies Ltd.
 */
abstract class PdoRepository extends Repository
{
	protected static $_lastStatement = "";

	protected static $_lastParams = [];

	protected static $_secondLastStatement = "";

	protected $lastSortsUsed = [];

	/**
	 * The default connection to use if an explicit connection isn't passed in.
	 *
	 * @var /PDO
	 */
	protected static $defaultConnection = null;

	/**
	 * Return's the default connection.
	 */
	public static function GetDefaultConnection()
	{
		if ( self::$defaultConnection === null )
		{
			$db = new StemSettings();

			self::$defaultConnection = static::getConnection( $db );
		}

		return self::$defaultConnection;
	}

	/**
	 * @param StemSettings $dbSettings
	 * @return \PDO
	 * @throws \Rhubarb\Stem\Exceptions\RepositoryConnectionException
	 */
	public static function getConnection( StemSettings $dbSettings )
	{
		throw new RepositoryConnectionException( "This repository has no getConnection() implementation." );
	}

	/**
	 * Discards the default connection.
	 */
	public static function ResetDefaultConnection()
	{
		self::$defaultConnection = null;
	}


	/**
	 * A collection of PDO objects for each active connection.
	 *
	 * @var /PDO[]
	 */
	protected static $connections = array();

	/**
	 * Returns the last SQL statement executed.
	 *
	 * Used by unit tests to ensure performance optimisations have taken effect.
	 */
	public static function GetPreviousStatement( $secondLast = false )
	{
		return ( $secondLast ) ? self::$_secondLastStatement : self::$_lastStatement;
	}

	/**
	 * Returns the last SQL parameters used.
	 *
	 * Used by unit tests to ensure interactions with the database are correct.
	 */
	public static function GetPreviousParameters()
	{
		return self::$_lastParams;
	}

	public function canFilterExclusivelyByRepository( Collection $collection, &$namedParams = [], &$propertiesToAutoHydrate = [] )
	{
		$filteredExclusivelyByRepository = true;

		$filter = $collection->getFilter();

		if ( $filter !== null )
		{
			$filter->filterWithRepository( $this, $namedParams, $propertiesToAutoHydrate );

			$filteredExclusivelyByRepository = $filter->wasFilteredByRepository();
		}

		return $filteredExclusivelyByRepository;
	}

	public function reHydrateObject( Model $object, $uniqueIdentifier )
	{
		unset( $this->cachedObjectData[ $uniqueIdentifier ] );

		$this->hydrateObject( $object, $uniqueIdentifier );
	}

	protected function getManualSortsRequiredForList( Collection $list )
	{
		$sorts = $list->getSorts();

		$sorts = array_diff_key( $sorts, array_flip( $this->lastSortsUsed ) );

		return $sorts;
	}

	/**
	 * Executes the statement with any supplied named parameters on the connection provided.
	 *
	 * If no connection is provided the default connection will be used.
	 *
	 * @param $statement
	 * @param array $namedParameters
	 * @param \PDO $connection
	 * @param bool $isInsertQuery True if the query is an insert and the ID should be returned
	 * @throws \Rhubarb\Stem\Exceptions\RepositoryStatementException
	 * @return \PDOStatement
	 */
	public static function executeStatement( $statement, $namedParameters = array(), $connection = null, $isInsertQuery = false )
	{
		if ( $connection === null )
		{
			$connection = static::GetDefaultConnection();
		}

		self::$_secondLastStatement = self::$_lastStatement;
		self::$_lastStatement = $statement;
		self::$_lastParams = $namedParameters;

		$pdoStatement = $connection->prepare( $statement );

		Log::CreateEntry( Log::PERFORMANCE_LEVEL | Log::REPOSITORY_LEVEL, function() use ( $statement, $namedParameters, $connection )
		{
			$newStatement = $statement;

			array_walk( $namedParameters, function( $value, $key ) use ( &$newStatement, &$params, $connection )
			{
				// Note this is not attempting to make secure queries - this is purely illustrative for the logs
				// However we do at least do addslashes so if you want to cut and paste a query from the log to
				// try it - it should work in most cases.
				$newStatement = str_replace( ':'.$key, $connection->quote( $value ), $newStatement );
			} );

			return "Executing PDO statement ".$newStatement;
		}, "PDO" );

		if ( !$pdoStatement->execute( $namedParameters ) )
		{
			$error = $pdoStatement->errorInfo();

			throw new RepositoryStatementException( $error[ 2 ], $statement );
		}

		if ( $isInsertQuery )
		{
			$pdoStatement = $connection->lastInsertId();
		}

		Log::CreateEntry( Log::PERFORMANCE_LEVEL | Log::REPOSITORY_LEVEL, "Statement successful", "PDO" );

		return $pdoStatement;
	}

	public static function executeInsertStatement( $sql, $namedParameters = [], $connection = null )
	{
		return self::executeStatement( $sql, $namedParameters, $connection, true );
	}

	/**
	 * Checks if raw repository data needs transformed before passing to the model.
	 *
	 * @param $modelData
	 * @return mixed
	 */
	protected function transformDataFromRepository($modelData)
	{
		foreach ($this->columnTransforms as $columnName => $transforms)
		{
			if ($transforms[0] !== null)
			{
				$closure = $transforms[0];

				$modelData[ $columnName ] = $closure( $modelData[ $columnName ] );
			}
		}

		return $modelData;
	}

	/**
	 * Executes the statement and returns the first column of the first row.
	 *
	 * @param $statement
	 * @param array $namedParameters
	 * @param null $connection
	 * @return string
	 */
	public static function returnSingleValue( $statement, $namedParameters = array(), $connection = null )
	{
		$statement = self::executeStatement( $statement, $namedParameters, $connection );

		return $statement->fetchColumn( 0 );
	}

	/**
	 * Returns the first row of results from the statement
	 *
	 * @param $statement
	 * @param array $namedParameters
	 * @param null $connection
	 * @return string
	 */
	public static function returnFirstRow( $statement, $namedParameters = array(), $connection = null )
	{
		$statement = self::executeStatement( $statement, $namedParameters, $connection );

		return $statement->fetch( \PDO::FETCH_ASSOC );
	}
}
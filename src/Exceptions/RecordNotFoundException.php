<?php

namespace Rhubarb\Stem\Exceptions;
use Rhubarb\Crown\Exceptions\RhubarbException;

/**
 * Thrown when a record could not be loaded.
 *
 * @author acuthbert
 * @copyright GCD Technologies 2012
 */
class RecordNotFoundException extends RhubarbException
{
	public $objectType;
	public $uniqueIdentifier;

	public function __construct( $objectType, $uniqueIdentifier = null )
	{
		$this->objectType = $objectType;
		$this->uniqueIdentifier = $uniqueIdentifier;

		parent::__construct( "A record of type '".$objectType."' could not be loaded for identifier '".$uniqueIdentifier."'" );
	}
}

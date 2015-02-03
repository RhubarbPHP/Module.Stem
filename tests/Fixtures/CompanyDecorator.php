<?php
/**
 * Created by JetBrains PhpStorm.
 * User: scott
 * Date: 22/08/2013
 * Time: 10:04
 * To change this template use File | Settings | File Templates.
 */

namespace Rhubarb\Stem\Tests\Fixtures;

use Rhubarb\Stem\Decorators\DataDecorator;

class CompanyDecorator extends DataDecorator
{
	public $singletonMonitor = false;

	protected function registerColumnDefinitions()
	{
		parent::registerColumnDefinitions();

		$this->addColumnDecorator( "CompanyName", function( Company $model, $formattedValue )
		{
			return "ABC" . $model->CompanyName;
		});

		$this->addColumnDecorator( "Balance", function( Company $model, $formattedValue )
		{
			if( $formattedValue == "" )
			{
				return "";
			}

			return "&pound;" . $formattedValue;
		});

		$this->addColumnFormatter( "CompanyID", function( Company $model, $formattedValue )
		{
			return str_pad( $formattedValue, 5, "0", STR_PAD_LEFT );
		});
	}

	protected function registerTypeDefinitions()
	{
		parent::registerTypeDefinitions();

		$this->addTypeFormatter( "\Rhubarb\Stem\Schema\Columns\Money", function( Company $model, $value )
		{
			return number_format( $value, 2 );
		});

		$this->addTypeDecorator( "\Rhubarb\Stem\Schema\Columns\Date", function( Company $model, $value )
		{
			return $value->format( "jS F Y" );
		});
	}
}
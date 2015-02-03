<?php

namespace Rhubarb\Stem\Repositories\MySql\Schema\Columns;

class LongText extends MediumText
{
	use MySqlColumn;

	public function getDefinition()
	{
		return "`".$this->columnName."` longtext ".$this->getDefaultDefinition();
	}
}

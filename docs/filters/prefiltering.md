# Filter By Default

Overriding the Model::Find() method allows for a model to be filtered by default (as well as
sorted).

For example, the following will ensure that only records without a DeletedFlag == true will be
returned in collections by default.

~~~php
<?php

public static function Find( Filter $filter = null )
{
	$deletedFlagFilter = new Equals( 'DeletedFlag', false );
	if( $filter === null )
	{
		$filter = $deletedFlagFilter;
	}
	else
	{
		$filter = new AndGroup( [ $filter, $deletedFlagFilter ] );
	}

	return parent::Find( $filter );
}

~~~

## Relationships

The model's Find() method is used to provide relationship collections. It is not used to find single
items in a relationship - so for example the "one" component in one-many or one-one relationships
will not be filtered using this method, but the "many" component will be. Note that while
collections are filtered by default, there is nothing to stop someone from removing the filter (e.g.
with Collection::ReplaceFilter()) so this is inappropriate for use as a method of data partitioning
as it's easily violated.

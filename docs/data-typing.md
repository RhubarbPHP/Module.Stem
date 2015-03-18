Data Typing and Transforms
===

Normally model data is stored in simple PHP primitive types (string, int, float etc.) and is
exactly what was returned by the repository. However in some cases smarter typing of the
data is required. This allows for two things:

1. The model can be provided with a data type that makes sense for the column.
2. The model can standardise the data provided to the model from the repository or user code.

The best example in the Core library are dates. If a model contains a date column Core will ensure
that regardless of the source and type of the assigned value, that it is converted to a
`CoreDateTime` class. This means once the value is retrieved it is always a `CoreDateTime` object
avoiding the need to ever manually create a date object to represent it. Consider these examples:

``` php

$contact->DateOfBirth = "2001-01-01";
print $contact->DateOfBirth->format( "d.m.Y" );
// 01.01.2001

$contact->DateOfBirth = mktime( 0,0,0,1,1,2001 );
print $contact->DateOfBirth->format( "d.m.Y" );
// 01.01.2001

$contact->DateOfBirth = "now";
print $contact->DateOfBirth->format( "d.m.Y" );
// [Now's date in dd.mm.yyyy format]

$contact->DateOfBirth = new DateTime( "2001-01-01" );
print $contact->DateOfBirth->format( "d.m.Y" );
// 01.01.2001
```

In each case you can see a DateTime object is returned. Note that you can set the value to be any of
the date stanzas supported by [strtotime](http://php.net/manual/en/datetime.formats.relative.php).

When the date column is committed to the repository however the column can transform the date object
into the correct string required. If you are using the MySql Date column variants for example this
will return a MySql date string.

## Data Transforms

Transforms happen at the schema `Column` level. There are four transformations possible:

1. Into the model data
2. From the model data
3. Into the repository storage
4. From the repository storage

Transforms are implemented by overriding the correct function within the relevant Column object:

1. GetTransformIntoModelData()
2. GetTransformFromModelData()
3. GetTransformIntoRepository()
4. GetTransformFromRepository()

The function should return an anonymous function which will accept a single parameter and return the
transformed value. The reason for returning an anonymous function is for performance. We did not
want to be calling a transform function for every assignment or fetch of model data. Instead the
model class first understands and then caches the transforms for each column that exist. For future
model access for that class a simple lookup is done to see if a transform exists.

Here's the stock Core Date object:

``` php
class Date extends Column
{
	/// ...

	public function GetTransformIntoModelData()
	{
		return function( $data )
		{
			return new CoreDateTime( $data );
		};
	}

	/// ...
}
```

Here we just ensure that any assignment sets the value to be a `CoreDateTime` object.

Here's the `MySql\Date` Variant:

``` php
class Date extends \Gcd\Core\Modelling\Schema\Columns\Date
{
	/// ...

	public function GetTransformIntoRepository()
	{
		return function( $data )
		{
			$date = $data->format( "Y-m-d" );

			return $date;
		};
	}

	public function GetTransformFromRepository()
	{
		return function( $data )
		{
			$date = new CoreDateTime( $data );

			return $date;
		};
	}

	/// ...
}
```

These transforms ensure that MySql receives a proper date string and that the date is parsed
properly upon loading data.

## Other Applications

This facility provides a tidy way to add additional packing and storage to your database abstraction
layer. For example you might store multiple values packed in some way (e.g. PHP serialization or
json encoding) into a single column. Transforms would let you inflate the packed data automatically
and provide access the data through the model as the correct object or array.

Another application would be encryption. An encryption column type might add transforms to ensure
data is encrypted on it's way to the repository and decrypted back again.
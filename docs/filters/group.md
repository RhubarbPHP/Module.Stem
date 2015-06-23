# Group

Group Filters are used to combine multiple filters, and make them behave as one filter. Multiple filters can be combined using "AND"
(meaning all filters must match), or "Or" where you require Models matching any of the grouped criteria are included. All examples are
taken from the corresponding Unit Test.

##The AddFilters Method

The add filters method can take any number of filters, and add them into the Group Filter. This can be called multiple times in
the same groupFilter object, but must be called at least once. This method will only accept valid Filter classes.

## Examples

### Simple "And" example
The example below will match all Models with a Forename containing "Jo" and a Surname containing "Johnson".
There is no real advantage for doing this over simply using two separate Contains Filters, unless you wish to take that
group filter and do something more complex with it later on.

```php
<?php
$filterGroup = new \Gcd\Core\Modelling\Filters\Group( "And" );
$filterGroup->AddFilters(
	new \Gcd\Core\Modelling\Filters\Contains( "Forename", "Jo", true ),
	new \Gcd\Core\Modelling\Filters\Contains( "Surname", "Johnson", true )
);
$this->list->Filter( $filterGroup );
```

### Simple "Or" example
The example below will match all Models with a Forename containing "Jo" OR a Surname containing "Johnson".

```php
<?php
$filterGroup = new \Gcd\Core\Modelling\Filters\Group( "Or" );
$filterGroup->AddFilters(
	new \Gcd\Core\Modelling\Filters\Contains( "Forename", "Jo", true ),
	new \Gcd\Core\Modelling\Filters\Contains( "Surname", "Johnson", true )
);
$this->list->Filter( $filterGroup );
```

### Example Involving group
As the group filter is an entirely normal filter, which behaves exactly as any other filter, it can operate on another group
filter - as in this example:

```php
<?php
$filterGroup1 = new \Gcd\Core\Modelling\Filters\Group( "And" );
$filterGroup1->AddFilters(
	new \Gcd\Core\Modelling\Filters\Contains( "Forename", "Jo", true ),
	new \Gcd\Core\Modelling\Filters\Contains( "Surname", "Jo", true )
);

$filterGroup2 = new \Gcd\Core\Modelling\Filters\Group( "Or" );
$filterGroup2->AddFilters(
	new \Gcd\Core\Modelling\Filters\Contains( "Surname", "Luc", true ),
	new \Gcd\Core\Modelling\Filters\LessThan( "DateOfBirth", "1980-01-01", true )
);

$filterGroup = new \Gcd\Core\Modelling\Filters\Group( "Or" );
$filterGroup->AddFilters(
	$filterGroup1,
	$filterGroup2
);
$this->list->Filter( $filterGroup );
```


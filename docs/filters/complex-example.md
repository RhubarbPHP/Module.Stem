A Complex Example - XOR
===

The example below shows how you can combine multiple filters to do whatever you would like to.
In this example we effectively have combined filters to make a XOR. So we will get all records with "Jo" in the forename
or the surname but NOT in both. You would be well advised to read up on some of the individual filters before looking
at this example.

~~~php
<?php

$filterOne = new \Gcd\Core\Modelling\Filters\Contains( "Forename", "Jo", true );
$filterTwo = new \Gcd\Core\Modelling\Filters\Contains( "Surname", "Jo", true );

$filterAnd = new \Gcd\Core\Modelling\Filters\Group( "And" );
$filterAnd->AddFilters(
	$filterOne,
	$filterTwo
);

$filterOr = new \Gcd\Core\Modelling\Filters\Group( "Or" );
$filterOr->AddFilters(
	$filterOne,
	$filterTwo
);

$filterNotAnd = new \Gcd\Core\Modelling\Filters\Not( $filterAnd );

$filterXor = new \Gcd\Core\Modelling\Filters\Group( "And" );
$filterXor->AddFilters(
	$filterNotAnd,
	$filterOr
);
$this->list->Filter( $filterXor );

~~~

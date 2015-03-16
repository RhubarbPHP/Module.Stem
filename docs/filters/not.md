# Not

Not filters are used to "not" whatever the filter supplied in the constructor does. i.e. to Include what the other filter excludes
and to exclude what the other filter includes

##Examples

###Simple Example###
Exclude all Modules with a Forename containing "jo"
~~~php
<?php

$notFilter = new \Gcd\Core\Modelling\Filters\Not( new \Gcd\Core\Modelling\Filters\Contains( "Forename", "jo" ) );
$this->list->Filter( $notFilter );

~~~


###Example with Groups
This example will exclude any Models with a Forename containing Jo OR a surname Containing Johnson
~~~php
<?php

$filterGroup = new \Gcd\Core\Modelling\Filters\Group( "And" );
$filterGroup->AddFilters(
	new \Gcd\Core\Modelling\Filters\Contains( "Forename", "Jo", true ),
	new \Gcd\Core\Modelling\Filters\Contains( "Surname", "Johnson", true )
);
$notFilter = new \Gcd\Core\Modelling\Filters\Not( $filterGroup );

~~~

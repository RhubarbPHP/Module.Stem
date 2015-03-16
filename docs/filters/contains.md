#Contains#
Data Filter used to keep all records with a variable which contains a given variable.

##Parameters

###$columnName
The column being used for the comparison.

###$contains
The value the column must contain, in order for the record to be included

###$caseSensitive
Set to true to make the match case sensitive


##Examples

###Keep all records with a Forename containing "jo", regardless of case

~~~php
<?php
$filter = new \Gcd\Core\Modelling\Filters\Contains( "Forename", "jo", false );
$this->list->Filter( $filter );
~~~

###Keep all records with a Forename containing jo

~~~php
<?php
$filter = new \Gcd\Core\Modelling\Filters\Contains( "Forename", "jo", true );
$this->list->Filter( $filter );
~~~



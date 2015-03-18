#Equals

Data Filter used to keep all records exactly matching a given variable.

##Parameters

$columnName
:    The column being used for the comparison.

$contains
:    The value the column must contain, in order for the record to be included


##Examples

Keep all records with a Forename of Tom:

```php
$filter = new \Gcd\Core\Modelling\Filters\Equals( "Forename", "Tom" );
$this->list->Filter( $filter );
```

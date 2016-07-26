# Filters #

Filters reduce Collections to a subset of matching Models. Filters are
expressed using Filter objects and in the absence of repository support
work by iterating over the items in a list and building a list of
records to remove from the collection. If it supports it, the repository
can use the filter directly to customise its query to avoid
expensive iteration.

Most filters operate on a single model record and apply a simple
expression, like equals, more than, less than, contains etc. Some
filters however are more complex. The `Group` filter for example
contains a collection of other filters ANDed or ORed together. The
`Not` filter inverts the selection of any other filter given to it.

## Basic Filtering

The basic set of filters perform the essentials of everyday filtering. Most take the column name to filter on
and a set of arguments to configure the filter.

Filter occurs in two main places: when you call that static `find()` function on a model class or if you call
`filter` on an existing collection instance.

Successive calls to filter on a collection combine the filters (like an AND expression in SQL).

``` php
// First method:
$contacts = Contact::find($filter);

// Second method:
$contacts = Contact::all();
$contacts->filter($filter);
```

### Equals

Selects rows where the value in $columnName matches the value $equals exactly

```php
new Equals($columnName, $equals)
```

#### Example

Keep all records with a first name of Tom:

```php
$contacts->filter(new Equals( "FirstName", "Tom" ));
```

### StartsWith

Selects rows where the value in $columnName starts with the value $startsWith. By default the search is
case insensitive. For a case sensitive search you should pass true to $caseInsensitive.

```php
new StartsWith($columnName, $startsWith, $caseInsensitive = false)
```

#### Example

Keep all records with a first name starting with Tom:

```php
$contacts->filter(new StartsWith( "FirstName", "Tom" ));    // Finds Tom, Tommy, Tombola
```

### EndsWith

Selects rows where the value in $columnName ends with the value $endsWith. By default the search is
case insensitive. For a case sensitive search you should pass true to $caseInsensitive.

```php
new EndsWith($columnName, $endsWith, $caseInsensitive = false)
```

#### Example

Keep all records with a first name ending with 'a':

```php
$contacts->filter(new StartsWith( "FirstName", "a" ));    // Finds Angela, Rebecca, SuzannA
```

### Contains

Selects rows where the value in $columnName contains the value $contains. By default the search is
case insensitive. For a case sensitive search you should pass true to $caseInsensitive.

```php
new Contains($columnName, $contains, $caseInsensitive = false)
```

#### Example

Keep all records with a first name containing with 'bar':

```php
$contacts->filter(new Contains( "FirstName", "bar" ));    // Finds Barbara, Allobar, Turbary
```

### GreaterThan

Selects rows where the value in $columnName is greater than value $greaterThan exclusive. To make it inclusive
(greater than or equals) pass true to $inclusive.

```php
new GreaterThan($columnName, $greaterThan, $inclusive = false)
```

#### Example

Keep all donation records where the amount is greater or equal to 500:

```php
$donations->filter(new GreaterThan( "Amount", 500, true ));
```

### LessThan

Selects rows where the value in $columnName is less than value $greaterThan exclusive. To make it inclusive
(less than or equals) pass true to $inclusive.

```php
new LessThan($columnName, $lessThan, $inclusive = false)
```

#### Example

Keep all donation records where the amount is less than or equal to 500:

```php
$donations->filter(new LessThan( "Amount", 500, true ));
```

### Between

Selects rows where the value in $columnName is between $min and $max inclusive.

```php
new Between($columnName, $min, $max)
```

#### Example

Keep all donation records where the amount is between 10 and 1000:

```php
$donations->filter(new Between( "Amount", 10, 1000));
```

### OneOf

Selects rows where the value in $columnName is found a fixed list of possible values, $oneOf.

```php
new OneOf($columnName, $oneOf)
```

#### Example

Keep all donation records where the amount is 10, 100 or 1000:

```php
$donations->filter(new OneOf( "Amount", [10, 100, 1000]));
```
Not
===

Not filters are used to invert whatever the filter supplied in the constructor does. i.e. to include what the
other filter excludes and to exclude what the other filter includes.

## Creating a Not filter

You can either construct a Not filter object and pass the filter to invert in the constructor or you can call the
`getInvertedFilter()` function on the existing filter.

``` php
// People not called John
$notEquals = new Not(new Equals("FirstName", "John"));
// Same again
$notEquals = (new Equals("FirstName", "John"))->getInvertedFilter();
```
This works with all other filters including groups. This example will remove all models called Jo Johnson.

```php
$filterGroup = new AndGroup(
    new Contains("Forename", "Jo"),
	new Contains("Surname", "Johnson")
);
$notFilter = new Not($filterGroup);
```

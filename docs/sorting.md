Sorting
===

Sorting a list is allowed for by two methods, `AddSort()` and `ReplaceSort()`. You can sort on any
property of the model even computed properties. Bear in mind that for large lists sorting can be
expensive. If the repository for your model is able to it can improve performance by sorting at the
back end data store (e.g. using ORDER BY statements). You can safely mix sorts that get done by the
back end with those that aren't e.g. database columns and computed properties - just bear in mind
that performance will be affected.

You can also sort by columns in related models using the dot operator, e.g. `Company.CompanyName`,
however the same reservations about performance must be borne in mind. If the repository supports it
auto hydration will be used to improve performance of sorting on related properties.

## AddSort()

To add an additional sort to an existing list simply call `AddSort()` passing the name of the column
and either true for ascending or false for descending sort:

``` php
$list->AddSort( "Surname", true );
$list->AddSort( "Forename", false );
// $list is now sorted by Surname ascending followed by Forename descending.
```

## ReplaceSort()

`ReplaceSort()` can be called with the same parameters as `AddSort()` however instead of adding an
additional sort it first removes all existing sorts.

You can also pass an array to `ReplaceSort()` with column name to direction boolean pairs:

```
$list->ReplaceSort( "Balance", true );
// Sorted by balance ascending
$list->ReplaceSort( 
    [ "Balance" => true, "Surname" => false ]
);
// Sorted by balance ascending followed by surname descending.
```

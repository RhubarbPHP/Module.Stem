Ranging and Paging
===

Model Lists support setting a range which restricts the number of rows returned to a particular sub set from within the main list. This allows for 'paging' a list into chunks of any size.

As with sorting the individual repository types will use their own ranging methods where available (e.g. LIMIT clause) for performance.

Note that if sorting has been applied and the repository was not able to fully utilise the back end store to do the sort, then it will also not be able to perform the ranging on the back end store either. Only once the sorting has been finalised can the range be applied. **This can mean that adding a sort on a computed column can have a drastic effect on performance for large lists, even if it is paged.**

To apply a range simply call `SetRange()` with the starting index and count:

``` php
$list->SetRange( 2, 4 );
// $list now restricted to return indexes 2, 3, 4 and 5
```

Also note that counting the list will always return the number of entries in the original list.

``` php
$list->SetRange( 2, 4 );
// $list now restricted to return indexes 2, 3, 4 and 5
print count( $list );
// Will print 88 if $list had 88 items before the range
```

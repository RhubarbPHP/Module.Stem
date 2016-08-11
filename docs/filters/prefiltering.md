# Filter By Default

Overriding the Model::find() function allows for a model to be filtered
and sorted by default. This allows you to make it difficult to create
a collection with important security filters in place.

For example, the following will ensure that only records without a
delete flag set will be returned in collections by default.

```php
public static function find(...$filters)
{
    $filters[] = new Equals( 'DeletedFlag', false );
	return parent::find(...$filter );
}

```

> Note that while this cause collections returned by `find` or `all` to
> be filtered by default, there is nothing to stop someone from removing
> the filter (e.g. with `replaceFilter()`) so this is an incomplete 
> approach to use as a method of data partitioning as it's easy
> to make a mistake that removes the data partitioning filter.

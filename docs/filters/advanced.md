Advanced Filters
================

In addition to the basic filters there are some more advanced filters
that can make life a little easier.

## Group based filters

The following filters simply combine basic filters into ready made
AndGroup or OrGroup filters saving some of the labour in crafting
complex searches. 

### AllWordsGroup

Selects models where **all** the words in $words are found in any
combination of columns passed in $columnNames. $words can be either
a string or an array of word strings. If a string is passed the string
is broken into an array of words using space characters.

```php
new AllWordsGroup($columnNames, $words)
```

#### Example

Select all product models where the name, description or keyword columns
contain all of the search terms:

```php
$products->filter(new AllWordsGroup(
                        [ "ProductName", "Description", "Keywords"],
                        [ "acme", "nail", "gun" ]
                    )
                  );
```

### AnyWordsGroup

Selects models where **any** of the words in $words are found in any
combination of columns passed in $columnNames. $words can be either
a string or an array of word strings. If a string is passed the string
is broken into an array of words using space characters.

```php
new AnyWordsGroup($columnNames, $words)
```

#### Example

Select all product models where the name, description or keyword columns
contain any of the search terms:

```php
$products->filter(new AllWordsGroup(
                        [ "ProductName", "Description", "Keywords"],
                        [ "acme", "nail", "gun" ]
                    )
                  );
```

## Other filters

### ColumnIntersectsCollection

Selects models where the value in $columnName can be found as a
unique identifier in the second collection $collection.

```php
new ColumnIntersectsCollection($columnName, $collection)
```

>This filter can be expressed as an [intersection](../intersections) instead and this is
>the preferred route as it gives much more control.

#### Example

Select all product models where the product's CategoryID is found in the
list of valid categories:

```php
// Find all products currently in active categories.
$products->filter(new ColumnIntersectsCollection(
                        "CategoryID",
                        Category::find(new Equals("Active", true))
                    )
                  );
```
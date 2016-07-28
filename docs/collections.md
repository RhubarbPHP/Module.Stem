Collections
===

A collection is a list of model objects that you can iterate over. Collections are normally created
by calling the `all()` or `find()` static method on a model class or by navigating through a one-to-many
relationship of a model object.

``` php
// Create a list of smiths:
$contacts = Contact::all();
$contacts->filter(new Equals( "Surname", "Smith" ) );

// Same thing, less code:
$contacts = Contact::find( new Equals( "Surname", "Smith" ) );

// Create a list of contacts from a relationship:
$company = new Company( 1 );
$contacts = $company->Contacts;
```

`Model::find()` and `Collection::filter()` can be given any number of filters as arguments and they will formed
into an `AndGroup` filter. They also support passing in filters as a single array argument.

## Iteration

As the `Collection` class implements `\Iterator`, `\ArrayAccess` and `\Countable` you can use the
list much as you would an array:

``` php
foreach( $contacts as $contact )
{
	// ...
}

for( $i = 0; $i < count( $contacts ); $i++ )
{
	$contact = $contacts[ $i ];
	// ...
}
```

The item returned by each iteration or array access is a model object of the class requested when
creating the collection.

## Filtering

Collections can be filtered for models matching one or more Filter expressions. Filtering is
abstracted away from any particular repository type and therefore you can filter on any property,
*even on computed properties*. It is the responsibility of the repository to provide
whatever performance optimisations it can, such as crafting SQL where clauses appropriately.

Read the [guide to filters](filters/) for an in-depth look at filters.

To filter a collection simply call `filter()` and pass a filter object. Sequential calls to filter
will combine filters (like an And operation).

``` php
$companies = Company::all();
$companies->filter(new StartsWith("Surname", "Smit"));
$companies->filter(new Contains("Forename", "John"));

// Finds John Smith, Johnny Smithson etc.
```

To filter with more complicated expressions you can use the AndGroup or OrGroup filters:

``` php
$companies = Company::all();
$companies->filter(
    new OrGroup(
        new Equals("Surname", "Smith"),
        new AndGroup(
            new Equals("Surname", "Doe"),
            new Equals("Forename", "Jane")
            )
        )
    );

// Finds all people with a surname of Smith OR Jane Doe
```

## Sorting

Sorting a collection is allowed for by two methods, `addSort()` and `replaceSort()`. You can sort on any
property of the model even computed properties. Bear in mind that for large collections sorting can be
expensive. If the repository for your model is able to it can improve performance by sorting at the
back end data store (e.g. using an ORDER BY statement). You can safely mix sorts that operate in the
back end with those that don't e.g. database columns and computed properties - just bear in mind
of the performance implications of doing this.

You can also sort by columns in related models using the dot operator, e.g. `Company.CompanyName`,
however the same reservations about performance must be borne in mind. If the repository supports intersections
it can improve performance of sorting on related properties.

addSort()
:	To add an additional sort to an existing list simply call `addSort()` passing the name of the column
	and either true for ascending or false for descending sort:

	``` php
	$list->addSort( "Surname", true );
	$list->addSort( "Forename", false );
	// $list is now sorted by Surname ascending followed by Forename descending.
	```

replaceSort()
:	`replaceSort()` can be called with the same parameters as `addSort()` however instead of adding an
	additional sort it first removes all existing sorts.

	You can also pass an array to `replaceSort()` with column name to direction boolean pairs:

	``` php
	$list->replaceSort( "Balance", true );
	// Sorted by balance ascending
	$list->replaceSort(
	    [ "Balance" => true, "Surname" => false ]
	);
	// Sorted by balance ascending followed by surname descending.
	```

## Grouping

Group a collection by calling `addGroup` with the name of the column to
group on. Iterating over the collection will return only one model per
group. The content of the other columns is indeterminate and might
vary from repository to repository. In most cases however the column
being selected for is the group column itself.

## Finding Models

Within a collection, filtered or not, you can search for a model with a particular unique identifier by
simply calling:

``` php
$model = $collection->findModelByUniqueIdentifier($myModelId);
```

If the model isn't in the collection a `RecordNotFoundException` will be thrown.

When processing user input this is the recommended way to create models if you have an existing collection
as a starting point. It protects you from the simple mistake of forgetting to validate user input against
what is appropriate for them to access and so defends against simple request manipulation attacks. For example:

``` php
// This is bad - we would have to remember to check that this ticket is allowed
// for this user.
$ticket = new Ticket($ticketId);

// This is better - it's not possible to get a ticket that isn't allowed for the user.
try {
    $ticket = $user->Tickets->findModelByUniqueIdentifier($ticketId);
} catch( RecordNotFoundException$er) {
    die("Sorry, invalid access attempt detected");
}
```

This is only a little slower than loading the model directly. It will require a hit on the model repository
but internally this refines the collection by extending it's filters to include the unique identifier so it
won't cause the entire collection to be loaded.

## Appending models to the Collection

New models can be appended to a collection by calling the `append` method:

``` php
$contact = new Contact();
$contact->Forename = "Andrew";

$contacts->append( $contact );
```

> Note that this has the side effect of saving new models if necessary in order to retrieve their unique
> identifier.

If the collection was filtered each filter will be able to set values on the model being appended
so that the model meets the constraints imposed by the filters on the collection. This will not work however if
the filters contain and OrGroup filter.

This pattern is the preferred way of attaching models to satisfy relationships as it lets you
write readable code like this:

```php
$contact = new Contact();
$contact->Forename = "Andrew";

$company = new Company( 3 );
$company->Contacts->append( $contact );

print $contact->CompanyID;
// Output: 3
```

This is easier to read and understand than setting the CompanyID manually, but also should the
filter returning Contacts change in future, the relationship will still be satisfied. For example
should the Contacts relationship be filtered so that it only returns contacts where Active is true, then
**adding a contact in this way will also set Active to 1**. This also means that adding an existing
*inactive* contact to the Contacts collection will reactivate it.

> Note that the model is appended to the end of the collection regardless of any sorting applied. If you
> need the new model returned in the correct position according the sorting on the collection you need
> to refetch the collection.

## Auto Hydration

Some [repositories](repositories) support a performance enhancement called "auto hydration". This
allows them to load related models at the same time as the primary model to avoid having to make
further round trips to the data store when those relationships are needed. For example the MySql
repository can implement an `INNER JOIN` to load relationship models along with the primary
model.

You can request this behaviour if you know that later in your code you will be accessing a
relationship for a large number of models. A classic example is where you are displaying a table
of data with some of the columns coming from a relationship:

``` php
$contacts = new Collection( "Contact" );
$table = new Table( $contacts );
$table->columns =
[
	"Title",
	"Forename",
	"Surname",
	"Company.CompanyName"
];

print $table;
```

In this example we might be printing 100 contacts and for each contact we'll have to make another
round trip to the database to get the related company. However consider the following amendment:

``` php
$contacts = new Collection( "Contact" );
$contacts->autoHydrate( "Company" );

$table = new Table( $contacts );
$table->columns =
[
	"Title",
	"Forename",
	"Surname",
	"Company.CompanyName"
];

print $table;
```

By calling `autoHydrate()` and passing the name of the Company relationship we provide a hint to the
repository that it should load the Company objects through auto hydration if it can.

> It is important to call autoHydrate() before any attempts to count, iterate or access elements
> of the Collection have taken place.

## Deleting Entries

The `Collection` class has a `deleteAll()` function which deletes all of it's models from the repository (by
calling delete() in turn on each model). This is obviously a dangerous function and should always be
used with caution. While iterating over the loop is much more expensive than deleting all items with a
matching query on the backend data store it offers a number of advantages:

* Each delete could be logged if model logging was important to the application
* Deleting individual items is safer when used in a replication environment

If large volumes of rows need removed it would still be best to use alternative methods such as using the
MySql repository `execute` method directly to perform a `DELETE` statement.

## Batch updates

Sometimes you need to update all models in a collection with the same changes. To do this you could simply iterate:

``` php
// Deactivate all contacts.
$contacts = Contact::all();

foreach( $contacts as $contact ){
	$contact->Active = false;
	$contact->save();
}
```

Iterating over a collection, especially large ones, is slow and doesn't scale well. In a 1,000
item collection the same update will involve 1,000 update queries instead of just 1. Traditionally an application
might use an UPDATE SQL statement to do this, which is fast and efficient but once we use hard coded SQL
statements, we loose the ability to easily unit test that code.

Instead the Collection class has a function called `batchUpdate` to which you can pass an associative array of property
name to values. The collection will update all items with the new values. The interesting thing about this is
that the repository can still optimise this back to a single update SQL statement under a number of conditions:

1. The Repository in use must support it
2. The filters on the collection must be entirely support by the repository
3. The collection must not involve auto-hydration or filtering on related models (i.e. no JOINs).

The example above can be rewritten as:

``` php
// Deactivate all contacts.
$contacts = Contact::find();
$contacts->batchUpdate( [ "Active" => false ] );
```

If any of the 3 rules above fail, calling batchUpdate will throw a `BatchUpdateNotPossible` exception. If you
are unsure if your Collection meets the criteria AND still want to support falling back to the iterative
approach (and you understand and accept the scalability issues that this might involve) you can pass true
as the optional second argument to this function to do iteration as a fallback if Repository updating fails.

As a general guide you should only call batchUpdate while passing true as a second parameter if you are
100% confident you require it, and that the size of any iteration is going to be within safe limits.

## Aggregates

Aggregates refer to any group based calculation on a column e.g. Sum, Count, CountDistinct, Average, Min, Max.
Aggregates is a more advanced topic and covered in full on the [aggregates](aggregates) manual entry page.

## Intersections

Often compared to SQL joins, [intersections](intersections) allow you to filter rows from a collection based upon rows matching
constraints within a second collection.

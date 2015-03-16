Collections
===

A collection is a list of model objects that you can iterate over. Collections are normally created
either by instantiating an instance of `Gcd\Core\Modelling\Collections\Collection` with a model
class name, by navigating through a one-to-many relationship of a model object or by calling the
`Find()` static method on a model class:

~~~ php
// Create a list of smiths manually:
$contacts = new Collection( "Contact" );
$contacts->Filter( new Equals( "Surname", "Smith" ) );

// Same thing, less code:
$contacts = Contact::Find( new Equals( "Surname", "Smith" ) );

// Create a list of contacts from a relationship:
$company = new Company( 1 );
$contacts = $company->Contacts;
~~~

## Iteration

A `Collection` object implements `\Iterator`, `\ArrayAccess` and `\Countable` so you can use the
list much as you would an array:

~~~ php
foreach( $contacts as $contact )
{
	// ...
}

for( $i = 0; $i < count( $contacts ); $i++ )
{
	$contact = $contacts[ $i ];
	// ...
}
~~~

The item returned by each iteration or array access is a model object matching the class name set on
the collection.

## Filtering

Collections work in tandem with the `Filter` object to allow a list to be filtered for matching
models. The filtering is abstracted away from any particular repository and therefore you can filter
on any property, even on computed properties. It is the responsibility of the repository to provide
whatever performance optimisations it can, such as altering SQL where clauses appropriately.

Read the [guide to filters](filters/index) to find out more.

## Finding Models

With a collection, filtered or not, you can search for a model with a particular unique identifier by
simply calling:

~~~ php
$model = $collection->FindModelByUniqueIdentifier( $myModelId );
~~~

If the model isn't in the collection a `RecordNotFoundException` will be thrown.

When processing user input this is the recommended way to create models if you have an existing collection
as a starting point. It protects you from the simple mistake of forgetting to validate user input against
what is appropriate for them to access and so defends against simple request manipulation attacks. For example:

~~~ php
// This is bad - we would have to remember to check that this ticket is allowed
// for this user.
$ticket = new Ticket( $ticketId );

// This is better - it's not possible to get a ticket that isn't allowed for the user.
try
{
    $ticket = $user->Tickets->FindModelByUniqueIdentifier( $ticketId );
}
catch( RecordNotFoundException $er )
{
    // Call the police....
}
~~~

This is only a little slower than loading the model directly. It will require a hit on the model repository
but internally this refines the collection by extending it's filters to include the unique identifier so it
won't cause the entire collection to be loaded.

## Appending Models to the Collection

New models can be appending to a collection by calling the `Append` method:

~~~ php
$contact = new Contact();
$contact->Forename = "Andrew";

$contacts->Append( $contact );
~~~

Note: This has the side effect of saving new models to retrieve their unique identifier.

If the collection has been filtered the filters will be given a chance to set values on the model
such that the same filters would match this new model. This also works when the filters are part of
a Group filter in AND boolean mode.

This pattern is the preferred way of attaching models to satisfy relationships as it lets you
implement the following code:

~~~php
$contact = new Contact();
$contact->Forename = "Andrew";

$company = new Company( 3 );
$company->Contacts->Append( $contact );

print $contact->CompanyID;
// Output: 3
~~~

This is easier to read and understand than setting the CompanyID manually, but also should the
filter returning Contacts change in future, the relationship will still be satisfied. For example
should the Contacts relationship be filtered so that it only returns contacts where Active = 1, then
**adding a contact in this way will also set Active to 1**. This also means that adding an existing
*inactive* contact to the Contacts collection will reactivate it.

~~~
Note that the model is appended to the end of the collection regardless of any sorting applied. This
is something we will consider changing in future versions so that the sort order is preserved.
~~~

## Auto Hydration

Some [repositories](data-repositories) support a performance enhancement called auto hydration. This
allows them to load related models at the same time as the primary model to avoid having to make
further round trips to the data store when those relationships are needed. For example the MySql
repository can implement an `INNER JOIN` to load the relationship models along with the primary
model.

This happens automatically if you are filtering or sorting on a related property, however you can
manually request this behaviour if you know that later in your program you will be accessing a
relationship for a large number of models. The classic example is where you are displaying a table
of data with some of the columns coming from a relationship:

~~~ php
$contacts = new Collection( "Contacts" );
$table = new Table( $contacts );
$table->Columns =
[
	"Title",
	"Forename",
	"Surname",
	"Company.CompanyName"
];

print $table;
~~~

In this example we might be printing 100 contacts and for each contact we'll have to make another
round trip to the database to get the related company. However consider the following amendment:

~~~ php
$contacts = new Collection( "Contacts" );
$contacts->AutoHydrate( "Company" );

$table = new Table( $contacts );
$table->Columns =
[
	"Title",
	"Forename",
	"Surname",
	"Company.CompanyName"
];

print $table;
~~~

By calling `AutoHydrate()` passing the name of the Company relationship we provide the hint to the
repository that it should load the Company objects through auto hydration if it can.

~~~
Note it is important to call AutoHydrate() before any attempts to count, iterate or access elements
of the Collection have taken place.
~~~

## Deleting Entries

The `Collection` class has a `DeleteAll()` method which deletes all the models from the repository (by calling
Delete() in turn on each model). This is obviously a dangerous call and should always be used with caution.
Note that while iterating over the loop is much more expensive than deleting all items with a matching query on the
backend data store it offers a number of advantages:

* Each delete could be logged if model logging was important to the application
* Deleting individual items is safer when used in a replication environment

If large volumes of rows need removed it would be best to use alternative methods such as using the MySql repository
static methods directly.
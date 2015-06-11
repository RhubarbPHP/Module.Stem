Schema and Relationships
===

## Model Schemas

A model schema describes the persistent properties of a model object including it's back end store
name, the 'columns' of the record and the name of it's unique identifier column. In order to populate or save a model
object a schema object must be returned by the model's `CreateSchema()` method.

``` php
class CoalBucket extends ModelObject
{
	public function CreateSchema()
	{
		$schema = new MySqlModelSchema( "tblCoalBucket" );

		$schema->labelColumnName = "BucketName";

		$schema->AddColumns(
			new AutoIncrement( "CoalBucketID" ),
			new String( "BucketName", 200 ),
			new Integer( "Capacity" )
		);

		return $schema;
	}
}
```

Notice in this example that we are using a `MySqlSchema` object and MySql specific column types.
These columns extend generic base classes however these specialised columns support the extra
options that the MySql database provides.

The `AutoIncrement` column type is special for MySql and it automatically sets the unique identifier
column name and adds a primary index. If your MySql schema doesn't have an auto increment column
(not yet supported) you would need to register these manually:

``` php
$schema->uniqueIdentifierColumnName = "CoalBucketID";
$schema->AddIndex( new Index( "CoalBucketID", Index::PRIMARY ) );
```

Our original example also states that, if required, a label for this model can be fetched by using
the BucketName column.

In code this is exposed by calling `GetLabel()` on the model object. e.g.

``` php
$bucket = new CoalBucket( 2 );
print $bucket->GetLabel();
```

Schemas are used by repositories to query and update the back end data stores and to ensure the
schema definition in the back end is up to date.

### Available Column Types

#### Base Column Types

These classes are extended by repository specific versions of columns and set out some basic
behaviours for various data types.

String
:   Text columns

Integer
:   Integer number columns

Float
:   Floating precision number columns

Boolean
:   Boolean columns

Date
:   Date columns

#### MySql Column Types

AutoIncrement
:   An Integer column that ensures autoincrement is enabled. Also becomes the unique identifier for
schema

TinyInt
:   MySql's tiny int type behaving as a boolean

Int, BigInt, Decimal
:   MySql's int, big int and decimal types

Date, DateTime, Time
:   Date, DateTime and Time types with data transforms to understand and convert MySql formatted expressions
into RhubarbDateTime etc. classes

Varchar, MediumText, Text
:   The most popular string column types

Enum, Set
:   Enums and sets take an array of possible values in their constructor

ForeignKey
:   Wraps the Int column and ensures an index is added to the schema as well.

### Indexes

As indexes provide disconnected model objects with no advantage they are not a feature of the base
Schema class however they can be declared on many of the database specific schema extensions. In
these cases they are only used when generating and updating the back end schema.

## Solution Schemas

A solution schema describes the model schemas involved in a solution and how those models relate
together. In this context *solution* could refer to the whole project but might equally well refer
to the solution provided by a module or any other smaller library. Multiple solution schemas can
co-exist at the same time.

While you don't need a solution schema in order to use model objects they provide several
advantages:

* Let you define one to one and one to many relationships allowing easy navigation in code between
model objects.
* Provides simple aliases for the model class names so that dependency injection becomes possible
with ordinary model objects (assuming that pattern is used from the start)
* Provides schema versioning and triggering of automatic back end schema updates when appropriate.

### Creating a Solution Schema

For performance reasons only the class name of a solution schema is registered with platform. This
ensures that solution schema objects are not instantiated until the modelling framework requires
them. Don't forget for some large projects the solution schema object will be very large and we want
to avoid instantiating it whenever possible. `SolutionSchema` is therefore an abstract class so you
must create your own extension of it:

``` php
class SiteSchema extends SolutionSchema
{
	public function __construct()
	{
		parent::__construct( 1.0 );

		$this->AddModel( "CoalBucket", "Gcd\Forge\CoalBucket" );
		$this->AddModel( "Coal", "Gcd\Forge\Coal" );
	}

	protected function DefineRelationships()
	{
		$this->DeclareOneToManyRelationships(
		[
			"CoalBucket" =>
			[
				"Coals" => "Coal.CoalBucketID"
			]
		]);
	}
}
```

The first task is to override the constructor and call the parent constructor with a version number.
Every time you change the schema of any of the models you should update this version number by a
small increment.

Next we call AddModel for each of the model classes we want to register with the schema. At the same
time we give the model a simple alias (normally the final part of the class name) that can be used
when defining relationships and, if required, for generating model objects. This approach lets you
register replace or extend a model class with another, either by registering a second solution
schema after the first, or by extending the solution schema class itself and adding the replacement
model in the original's stead.

Finally we declare a one to many relationship between the CoalBucket and Coal (not we're using the
model alias names here). It is important that relationships are defined in this function and **not
in the constructor**. If you do define relationships in the constructor you will end up in an
infinite loop as the solution schema searches for knowledge about the classes involved in the
relationship which (as this schema isn't registered yet) will cause the schema to be created again.

Relationships let you navigate between the models like this:

``` php
$coals = $coalBucket->Coals;
$coalBucket = $coals[0]->CoalBucket;
```

### Note:
```
Under the bonnet relationships are implemented using classes that extend `Relationship`.
`DeclareOneToManyRelationships` are merely convenience methods that
wrap around the internal function `SolutionSchema::AddRelationship()`.

`AddRelationship` registers a navigation property on a model that will instantiate the relationship
class and call it's `FetchFor` method to return the matching object or list.
```

### Relationship Declaration Syntax

There are three methods you can call to quickly declare relationships in your schema:

DeclareOneToManyRelationships
:	Declares one or more "one-to-many" relationships and the corresponding reverse "one-to-one"
relationship

DeclareOneToOneRelationships
:	Declares one or more "one-to-one" relationships and the corresponding reverse "one-to-one"
relationship

DeclareManyToManyRelationships
:	Declares one or more "many-to-many" relationships and the corresponding reverse "many-to-many"
relationship

The syntax of `DeclareOneToManyRelationships` and `DeclareOneToOneRelationships` is the same:

``` php
$this->DeclareOneToManyRelationships(
[
	"OneModelName" =>
	[
		"CollectionName" => "ManyModelName.OneModelPrimaryKeyNameInManyModel"
	]
] );
```

Putting this into practice with the familiar example of a relationship between customer and orders:

``` php
$this->DeclareOneToManyRelationships(
[
	"Customer" =>
	[
		"Orders" => "Order.CustomerID"
	]
] );
```

This is actually the condensed version of the declaration. We're assuming that the relationship is
using the unique identifier (CustomerID) of the Customer model and that the reverse relationship
will have the name "Customer". The previous example is syntactically equivalent to:

``` php
$this->DeclareOneToManyRelationships(
[
	"Customer.CustomerID" =>
	[
		"Orders" => "Order.CustomerID:Customer"
	]
] );
```

Here we've added a column name after "Customer" and we've added a colon followed by the name of the
reverse relationship.

This allows for occasions where one table relates to another table several times. For example
consider the following:

``` php
$this->DeclareOneToManyRelationships(
[
	"User" =>
	[
		"UpdatedTickets" => "Ticket.LastUpdatedByUserID:LastUpdatedBy",
		"CreatedTickets" => "Ticket.LastCreatedByUserID:LastCreatedBy",
		"AssignedTickets" => "Ticket.AssignedtoUserID:AssignedTo"
	]
] );
```

### Many-to-Many Relationships

`DeclareManyToManyRelationships` takes a slightly different syntax as we must also include the name
of the linking table in the definition.

``` php
$this->DeclareManyToManyRelationships(
[
	"Product" =>
	[
		"Categories" => "ProductCategory.ProductID_CategoryID.Category:Products" ]
	]
] );
```

Many to many relationships simplify your code by removing the need to 'daisy-chain' through the
intermediate model object. Also items can be added to the collection without having to create the
intermediate row yourself:

``` php
$product = new Product( 1 );
$category = new Category( 1 );

$product->Categories->Append( $category );
// OR
$category->Products->Append( $product );
```

### Registering the schema

In your site module (settings/modules.php) and the `Initialise` method you should add the following:

``` php
SolutionSchema::RegisterSchema( "Gcd\Site\SiteSchema" );
```

### Updating the back end schemas

Currently this process is not triggered automatically as it would require instantiating the solution
schema objects for every request just to see if the version number has changed. In production we
expect this will be a task for the deployment sub systems to complete.

In development to trigger the schema checking you need to contrive a callable script somewhere that
will ask each schema if it needs to update:

``` php
<?php

namespace Gcd\Site\Mvp\utility;

use Gcd\Core\Response\HtmlResponse;
use Gcd\Core\IGeneratesResponse;
use Gcd\Core\Modelling\Schema\SolutionSchema;

/**
 * A simple script to trigger schema versioning
 *
 * @author acuthbert
 * @copyright GCD Technologies 2013
 */
class UpdateSchema implements IGeneratesResponse
{
	public function GenerateResponse($request = null)
	{
		$schemas = SolutionSchema::GetAllSchemas();

		foreach( $schemas as $schema )
		{
			$schema->CheckModelSchemasIfNecessary();
		}

		$response = new HtmlResponse();
		$response->Content = "Done";

		return $response;
	}
}
```

Models And Schemas
===

A model class allows us to model the data of a single record found in a real world data store. The most
common data store is a database such as MySQL. Models let us create, load, modify and delete records.

As a PHP object they provide an excellent container for any business logic relating to that type of data.

This introduction is a guide to creating and using your first model class which will teach some of the key
concepts along the way.

To start modelling you need to create your own model classes. Any class that extends the `Model` base class is
called a Model class.

``` php
class Customer extends Model
{
}
```

> Note it's normal practice for model objects to be in a folder called Models in your src folder. If you
> have a lot of models you should try and organise them into relevant sub folders.

Model objects put data they need to keep in the data store in a special array called `$modelData`. This is
because some model operations (for example change detection) must manipulate or consider the full set of
stored data and having that data in an array is a great advantage.

The array is not public however so access must be provided by creating getter and setter functions.

Some model classes can have many dozens of columns stored in the data store. Creating getters and setters for
all of these is tedious and clutters the class with a large volumn of 'plumbing' code. Models therefore provide
your class with a [magical getter and setter](http://uk.php.net/manual/en/language.oop5.magic.php) which assumes
any unknown property will be accessed in the `$modelData` array.

## Accessing properties

With this in mind you can start using properties directly on your new class:

``` php
$customer = new Customer();
$customer->Forename = "John";   // Set the Forename property

print $customer->Forename;      // Get the Forename property
```

A disadvantage of using a magical getter and setter is that your IDE will not be able to autosuggest
property names as you type. Therefore it is good practice to use a PHPDoc comment to indicate the
magical properties that you know to be used in your model:

``` php
/**
 * Models a customer record.
 *
 * @property string $Forename The forename of the customer
 */
class Customer extends Model
{
}
```

> Note that the casing of magical properties is important and should match the casing of the actual field
> in your data store. UpperCamelCase is a good choice as it helps distinguish the magical properties from
> public properties of the class.

While your model is an object it also implements the PHP `ArrayAccess` interface so you can also access
the magical properties using an array syntax:

``` php
print $customer["Forename"];
```

## Defining a Schema

Without a schema your model object cannot move the data in and out of the data store. It doesn't know how
to reference it's location (e.g. table name in a database) or what type of columns to create.

You must define a schema for your model by implementing the `createSchema` function.

> Note that the createSchema function is abstract and so you can't actually create a Model class without it.

``` php
class Customer extends ModelObject
{
	public function createSchema()
	{
		$schema = new ModelSchema( "tblCustomer" );

		$schema->addColumn(
			new AutoIncrement( "CustomerID" ),
			new ForeignKey( "CustomerID" ),
			new String( "Forename", 200 ),
			new String( "Surname", 200 ),
			new Integer( "LastOrderID" )
		);

		return $schema;
	}
}
```

Using the `addColumn` method on the ModelSchema object we've registered a range of different columns. The first
is an auto increment column which will generate a new ID for each new record saved. The next is a ForeignKey
column which is an integer that in some repositories will generate an index.

The remaining columns are more simple - two string columns and an integer.

Column objects are responsible for generating the correct column type in the data store but they also convert
the raw repository data into a form more suitable for your application.

A `CommaSeparatedList` column for example creates itself as a string field in the repository put presentes
the data stored there as an array. The `CommaSeparatedList` column will convert the array to a comma separated
string when saving and restore it to an array when loading data.

View the full list of [available column types](columns)

## Registering your model

Models are registered in groups called a "Solution Schema". A solution schema defines a list of models
needed in the application and gives them a short alias name. We generally have one solution schema per
application. Let's create a new solution schema and register our model.

``` php,[7]
class MyAppSolutionSchema extends SolutionSchema
{
    public function __construct()
    {
        parent::__construct( 0.1 ); // Version 0.1 of our schema

        $this->addModel( "Customer", __NAMESPACE__ . '\Customer' );
    }
}
```

You'll notice that the constructor defines a version number. This is important - as you make changes to your
solution schema by adding new models or changing the columns in a model you should increment the version number.
This will signal to your application that it should refresh the actual data store with the new schema settings.

The `addModel` function takes an alias followed by the fully qualified class name of the model you're registering.

Giving models an alias opens up the possibility of importing someone elses project as a module and then
extending or even replacing the functionality of a model by registering a new model class with the same alias.

## Registering your solution schema

Finally you need to make sure your application is registering this solution schema with Stem. You do this in
the `initialise()` method of your application's module class in `app.config.php`

``` php
SolutionSchema::registerSolutionSchema( "myapp", __NAMESPACE__ . '\Models\MyAppSolutionSchema' );
```

## Saving and Loading Records

To store ('save') a model into the data store simply call the `save()` function.

``` php
$customer = new Customer();
$customer->Forename = "John";
$customer->Surname = "Doe";
$customer->save();
```

If this is a new model the record will be inserted into the data store. If it is an existing model the record
will be updated. Note, only properties that have changed will be updated in the data store.

If you know the ID of a record you can load it simply by creating the model using the ID as the constructor
argument.

``` php
$customer = new Customer( $customerId );    // Load the customer with the ID in $customerId
$customer->Surname = "Smith";
$customer->save();                          // Update surname to Smith
```

> If you try and load a record that doesn't exist the constructor will throw a `RecordNotFoundException`.

## Computed Properties

Model objects are good places to put functions that calculate values based on the model state. For example
the customer's balance might be calculated by calling a `getBalance()` function on the Customer model.

Functions on a model that start with the `get` prefix can also be accessed as magical properties. For example

``` php
class Customer extends Model
{
    // ...

    public function getBalance()
    {
        $balance = 100; // In reality some computation of the customer balance.
        return $balance;
    }
}

print $customer->Balance;
```

Simply drop the `get` and the `()` and you can access that method like it's a normal property of the class.
Why is this useful?

1. Sometimes you need to use these computed values in templates. Any system that can access class properties
   (but not methods) can now access these functions too.
2. This can be used to override an actual database column with a computed value. Sometimes this is invaluable
   if you're refactoring your application and need to change a flat column to a live computed value.

In addition to `get` functions you can also access functions prefixed with `set` in a similar way.

## Navigating relationships

Once [relationships](relationships) have been defined you can navigate from one model to another or to a
list of models simply by accessing the correct relationship property name.

``` php
$company = $contact->Company;       // Get the Company object associated with this contact
$contacts = $company->Contacts;     // Get the contacts as a collection for this company
```

Relationships are one of the most useful features of an ORM tool like Stem and improve the clarity of your
code. You are strongly advised to use them where you can.

One nifty feature of relationship properties is that you can navigate them through a 'dot' operator syntax
when accessing properties of your model. For example:

``` php
$companyName = $contact[ "Company.CompanyName" ];
```

This will get the CompanyName property of the Company model connected to our `$contact` object. Why
is this useful?

1. Templates can drill through the relationships simply by using the dot notation in field placeholders
2. Other sub systems of Stem can make use this feature for example filtering and sorting.

Performance permitting, there is no limit to how deep you can drill with this pattern.

## Finding a record

To load a record without a unique identifier, you can call the static `findFirst()` or `findLast()` methods.
Without an argument this will find the first and last record respectively. You can however pass a filter
object which will narrow the search and again return the first or last result found.

For more information on filters see [collections](collections) and [filters](filters)

``` php
$customer = Customer::findFirst(new Equals("CustomerName", "Acme Inc."));
```

Interestingly this will work on computed properties too:

``` php
$customer = Customer::findFirst(new GreaterThan("Balance", 500));
```

> Be aware that filtering on computed properties can be slow as all the data must be fetched from the data
> store and then compared after the computation has been done for every record returned.

If no matches are found by `findFirst` or `findLast` a RecordNotFoundException is thrown.

Where common searches will be done the best pattern is to create additional static methods to wrap
the `findFirst()` method:

``` php
class User extends Model
{
    public static function fromEmail($email)
    {
        return self::findFirst( new Equals("Email", $email));
    }
}

// Much faster to read and understand than using findFirst.
$user = User::fromEmail( $emailAddress );
```

## Deleting a record

Simply call `delete()`

``` php
$model->delete();
```

Note that this removes the object from the back end repository and from the local cache, so if you
try to use an existing collection that previously contained the object, you might get unpredictable
results.

## Tracking Changes

The model class keeps track of what is changing.

* Call `hasChanged()` to determine if the model data has changed since the last change snapshot was taken.
* Call `takeChangeSnapshot()` to capture the current model data and use that as its base to compare with.
* An observer or the model itself can receive notifications when properties in the model are changed. See
  [Model Events](events)

## Exporting and Importing Data

On occasion you need to move model data in and out of the model in bulk

* Call `exportRawData()` to export the underlying model data as an associative array. Magical getters are
  not used.
* Call `importRawData()` to import an associative array directly into the underlying model data. The model data
  is replaced, not merged. Magical setters are not used. The protected function `onDataImported()` is called
  after the import.

Often you need a representation of a model that is for public consumption, whether that be an API
end point or simple serialization (where you can't be sure the data won't be inspected or tampered with).
Necessarily we need to define which properties should be available for public export. You do this by
overriding the `getPublicPropertyList()` method and simply return an array of properties names.
This can include the names of computed properties.

This list of public properties controls two methods:

* `exportPublicData()` exports the values (if they exist) of all public properties
* `serializeModelDataAsJson()` takes the response from exportPublicData() and encodes it as a json
string.

## Sanitising Model Data

Often it's appropriate to take various actions when a model is being saved:

* Populating columns that are built from other columns in the model to save time searching e.g.
Formatting an OrderID into an OrderNumber column
* Populating foreign keys that allow for faster searching by reducing the number of joins e.g.
automatically adding the AddressID to an Order by copying it from the Customer
* Updating balance or outstanding amount columns on a header model when the children are saved

To do this simply override one of two methods: `beforeSave()` or `afterSave()`. `beforeSave()` is
called before the repository is given the model to store. `afterSave()` is called after the repository
has stored the model. If it was a new record it should have a unique identifier at this point.

> Note: Calling `save()` from within these methods will cause an infinite loop and shouldn't be done.

## Advanced Topics

* [Relationships between models](relationships)
* [Model Consistency](consistency)
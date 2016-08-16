Models And Schemas
===

A model class allows us to represent the data of a single item found in a data store ([repository](repositories)). The most
common repository is a database such as MySQL. Models let us create, load, modify and delete records and
navigate through the relationships between them.

They are also the normal container for any business logic internal to that type of data.

## Creating a Model object

To start modelling you need to create your own model classes. Any class that extends the `Model` base class is
called a Model class. e.g.

``` php
class Customer extends Model
{
}
```

> Note it's normal practice for model objects to be in a folder called Models in your src folder. If you
> have a lot of models you should try and organise them into folders.

Model objects store data internally in a special array called `$modelData`. The array is not public however so access
must be provided by creating getter and setter functions.

Some model classes can have dozens of columns in the repository. Creating getters and setters for all of these
is tedious and clutters the class with a large volume of plumbing code. The Model base class therefore provides
[magical getters and setters](http://uk.php.net/manual/en/language.oop5.magic.php) which automatically
map unrecognised properties to the `$modelData` array.

## Accessing properties

With this in mind you can start using properties directly on your new class:

``` php
$customer = new Customer();
$customer->Forename = "John";   // Set the Forename property

print $customer->Forename;      // Get the Forename property
```

A disadvantage of using a magical getter and setter is that your IDE will not be able to autosuggest
property names as you type. Therefore it is good practice to use a
[DocBlock](https://www.phpdoc.org/docs/latest/references/phpdoc/basic-syntax.html) comment to indicate properties
that you know are available in your model:

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

> The casing of magical properties is important and should match the casing of the actual field
> in your repository. UpperCamelCase is a good choice for column names as it helps distinguish magical properties
> from normal public properties of the class.

Although your model is an object, because it implements the PHP `ArrayAccess` interface you can also access the
magical properties using the array syntax:

``` php
print $customer['Forename'];
```

## Defining a Schema

Without a schema your model object cannot move the data in and out of the repository. It doesn't know it's
location (e.g. connection to server or table name in the database) or what type of columns exist there.

You must define a schema for your model by implementing the `createSchema` function.

> Note that the createSchema function is abstract and so you can't create a Model class without providing one.
> The first example in this document is only to illustrate a point and would actually fail.

``` php
use Rhubarb\Stem\Schema\ModelSchema
use Rhubarb\Stem\Schema\Columns\AutoIncrement;
use Rhubarb\Stem\Schema\Columns\ForeignKey;
use Rhubarb\Stem\Schema\Columns\Integer;
use Rhubarb\Stem\Schema\Columns\String;

class Customer extends ModelObject
{
	public function createSchema()
	{
		$schema = new ModelSchema( "tblCustomer" );

		$schema->addColumn(
			new AutoIncrementColumn( "CustomerID" ),
			new ForeignKeyColumn( "CustomerID" ),
			new StringColumn( "Forename", 200 ),
			new StringColumn( "Surname", 200 ),
			new IntegerColumn( "LastOrderID" )
		);

		return $schema;
	}
}
```

Using the `addColumn` method on the ModelSchema object we've registered a range of different columns. The first
is an auto increment column which will generate a new ID for each new record saved. The next is a ForeignKey
column which is an integer that in some repositories will also be indexed.

The remaining columns are more simple - two string columns and an integer.

Column objects are responsible for generating the correct column type in the data store but they also convert
the raw repository data into a form applicable to your application.

A `CommaSeparatedListColumn` for example creates itself as a string field in the repository put presents
the data stored there as an array. The `CommaSeparatedListColumn` will convert the array to a comma separated
string when saving and restore it to an array when loading data.

View the full list of [available column types](columns)

All schemas must define which column serves as the unique identifier for this model (think of this as the
primary key). We don't define one explicitly here, but because we've used an AutoIncrementColumn it's been
selected for use automatically. To explicitly set it, simply set `$schema->uniqueIdentifierColumnName` before
you return it.

## Registering your model

Models are registered in groups called a "Solution Schema". A solution schema defines a list of models
needed in the application and gives them each an alias. It also defines the relationships between the
models that it contains.

We generally have one solution schema per application or scaffold.

Let's create a new solution schema and register our model.

``` php
class MyAppSolutionSchema extends SolutionSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->addModel('Customer', Customer::class, 0.1); // Version 0.1 of our customer model
    }
}
```

The `addModel` function takes an alias followed by the fully qualified class name of the model you're registering.
Finally it can take a version number - this is important: as you make changes to your solution schema by adding new
models or changing the columns in a model you should increment the version number. This will signal to your
application that it should refresh the schema of the back end data store to match.

> Giving models an alias allows use to supplant an existing registered model with a different one. This ability is
> essential to making scaffolds.

## Registering your solution schema

Finally you need to make sure your application is registering the solution schema itself. You do this in
the `initialise()` method of your application class (or module class if building a module)

``` php
SolutionSchema::registerSolutionSchema('myapp', MyAppSolutionSchema::class);
```

Again the schema has an alias allowing whole solutions schemas to be replaced.

## Creating or migrating the repository schema

If you continually bump up the version numbers of individual models or the overall schema then
the repository schema could be migrated automatically when the schema is next used
depending on the features of Stem actually used.

For greater control over migration you should consider running the [update-schemas](custard/update-schemas) custard command
directly when you know the schema needs migrated. When deploying applications to production
especially using a continuous delivery approach the custard command is the preferred way
to do the migration.

### What can and cannot be migrated:

The migration system is conservative in that if Models or columns have been removed
they will not be removed from the back end data store. This is a safe guard against
irreversible accidents but can itself lead to undesirable situations in some cases.

Changes to column types, lengths and defaults will all be effected even if they are
destructive.

Occasionally a schema change cannot be effected because of existing data that no longer
meets the new constraints. In this case an exception will be thrown - it pays to invest
in a staging platform for our application to case this (rare) situation.

## Saving and loading records

To store ('save') a model into the repository simply call the `save()` function.

``` php
$customer = new Customer();
$customer->Forename = "John";
$customer->Surname = "Doe";
$customer->save();
```

If this is a new model the record will be inserted into the repository. If it is an existing model the record
will be updated. Note, only properties that have changed should be updated in the data store to minimise the
likelihood of conflicts.

If you know the ID of a record you can load it simply by creating the model using the ID as the constructor
argument.

``` php
$customer = new Customer($customerId);      // Load the customer with the ID in $customerId
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
        $balance = 100; // In reality this would be a calculation of the customer balance.
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
   if you're refactoring your application and need to change a flat column to a run time computed value.

In addition to defining a `get` function you can also define a `set` function which will be passed the value
being set as an argument. This is often used to disable modification of particular properties or to validate
the value being set.

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

This will get the CompanyName property of the Company model connected to our `$contact` object. This allows
templates to drill through the relationships simply by using the dot notation in field placeholders. This
convention is also supported when filtering, sort or aggregating on columns in a collection.

Performance permitting, there is no limit to how deep you can drill with this pattern in most cases.

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

> Be aware that filtering on computed properties can be slow as all records must be selected, returned from the
> repository, the value computed and *only then* evaluated in PHP.

If no matches are found by `findFirst` or `findLast` a RecordNotFoundException is thrown.

If loading a record by searching in this way will be a common task in your model you should create an additional
static method to wrap the `findFirst()` method e.g.

``` php
class User extends Model
{
    public static function fromEmail($email)
    {
        return self::findFirst(new Equals("Email", $email));
    }
}

// Much faster to read and understand than using findFirst.
$user = User::fromEmail($emailAddress);
```

## Deleting a record

Simply call `delete()` to delete a record.

``` php
$model->delete();
```

Note that this removes the object from the back end repository and from the local cache, so if you
try to use an existing collection that previously contained the object, you might get unpredictable
results.

A delete does *not* cascade through relationships so you should be careful not to orphan records in other
model types.

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
We need to define which properties should be available for public exposure. You do this by
overriding the `getPublicPropertyList()` method and simply return an array of property names.
This can include the names of computed properties.

This list of public properties is used by two methods:

* `exportPublicData()` exports the values (if they exist) of all public properties
* `serializeModelDataAsJson()` takes the response from exportPublicData() and encodes it as a json
string.

## Checking Model Data

One of the Model's most important jobs is to verify the sanity of the data it's being asked to store.
When you build models you should define the columns that are required under the appropriate circumstances.

For example a `Customer` might not be valid if it doesn't have an AccountCode *unless* that Customer is
marked as a "Cash Customer".

These rules should be expressed by implementing the `getConsistencyValidationErrors()` function and returning
an associative array of errors after your analysis. To codify the example above you would generate the following
code:

``` php
class Customer extends Model
{
    protected function getConsistencyValidationErrors()
    {
        $errors = [];

        if ( $this->AccountCode == "" && $this->AccountType != "Cash Customer" ){
            $errors[ "AccountCode" ] = "Account code must be populated if the customer isn't a cash customer";
        }

        return $errors;
    }
}
```

> Implementing validation rules is perhaps one of the most important tasks when setting up a
> business model as stopping damaged and invalid data entering your application saves countless
> hours in production spent supporting and fixing data inconsistencies.

Calling `save()` on an inconsistent model will throw a ModelConsistencyValidationException:

``` php
$customer = new Customer();
$customer->AccountType = "Credit Customer";

try {
    // This will fail as credit customers needs an account code.
    $customer->save();
} catch( ModelConsistencyValidationException $er ) {
    // The getErrors() function will retrieve the validation errors.
    var_dump( $er->getErrors() );
}
```

## Detecting new model status

All models must describe a single unique identifier column. If the model has this value then it is
an existing model. If it doesn't it is a new model. To help create clear code the Model class provides
a `isNewRecord()` function which returns *true* or *false* as just described.

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

Note: Calling `save()` from within these methods can cause an infinite loop!

> If your custom save action needs to update other models carefully consider if your model has the [authority
> to do so](inter-model).

In this example we set the CreatedDate when saving a new model only.

``` php
class Customer extends Model
{
    public function beforeSave()
    {
        if ($this->isNewRecord()){
            $this->CreatedDate = "now";
        }
    }
}
```
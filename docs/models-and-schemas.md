Model Objects
===

To create a model object you need to create a class that extends the `Model` base class:

~~~ php
class CoalBucket extends Model
{
}
~~~

## Accessing properties

The `Model` class extends the core `ModelState` class to expose it's internal data through magical
getters and setters.
This allows for very clean and readable code but also lets you set properties without having to
define or declare them
first:

~~~ php
$myModel = new CoalBucket();
$myModel->LumpsOfCoal = 3;

print $myModel->LumpsOfCoal;
~~~

It is good practice however to use a PHPDoc comment to reveal the magical properties that you expect
to be used with
your model:

~~~ php
/**
 * Models a coal bucket
 *
 * @property int $LumpsOfCoal The number of lumps of coal in the bucket.
 */
class CoalBucket extends Model
{
}
~~~

Note that the naming convention for magical properties is UpperCamelCase to distinguish it from
public fields which
would be lowerCamelCase.

## Computed Properties

You can override the getter and/or setter for any property or define new ones by implementing a
`GetPropertyName` or
`SetPropertyName` function. This allows you to create computed properties (which work seamlessly
with other classes and
tools such as templating engines) or to retrospectively supply correcting code at a later date (for
example trimming a
value, or upper casing a reference etc.)

~~~ php
/**
 * Models a coal bucket
 *
 * @property int $LumpsOfCoal The number of lumps of coal in the bucket.
 * @property bool $FireRisk Whether or not we represent a fire risk
 */
class CoalBucket extends Model
{
   public function GetIsFireRisk()
   {
      return ( $this->LumpsOfCoal > 10 );
   }

   public function SetLumpsOfCoal( $lumps )
   {
       if ( $lumps > 100 )
       {
          die( "Sorry, too many lumps. Also this is a terrible way to die." );
       }

       $this->modelData[ "LumpsOfCoal" ] = $lumps;
   }
}
~~~

You can call `isset` and `unset` just as you would a standard object. Note that calling
`isset`/`unset` on a property
with a magical getter will have unpredictable results.

You can also treat the object like an array, accessing properties using the property name as the
key.

~~~ php
print $model[ "LumpsOfCoal" ];
~~~

## Related models and model properties

Once [relationships](schema) have been defined you can navigate from one model to another or to a
list of models simply
by accessing the correct navigation property name.

~~~ php
$company = $contact->Company;
$contacts = $company->Contacts;
~~~

You can also access related model properties by using the dot operator when accessing the model like
an array:

~~~ php
$companyName = $contact[ "Company.CompanyName" ];
~~~

Performance permitting, there is no limit to how deep you can drill with this convention. A number
of other modelling
functions such as filtering and sorting also use and rely upon this convention.

## Loading existing records

To model an existing record in the back end data store, simply instantiate the model object passing
the unique
identifier in the constructor:

~~~ php
// Instantiate coal bucket '3'
$model = new CoalBucket( 3 );
~~~

If that record doesn't exist a `RecordNotFoundException` will be thrown. The model data will be
loaded using the
model's repository. [Find out more about repositories here.](data-repositories)

## Searching for records

To search for a record instead of loading using the unique identifier, you should call the
static `Find()` method:

~~~ php
$heavyBuckets = CoalBucket::Find( new GreaterThan( "LumpsOfCoal", 10 ) );
~~~

Interestingly this will work on computed properties too:

~~~ php
$bucketsToBeKeptOutside = CoalBucket::Find( new Equals( "IsFireRisk", true ) );
~~~

Additionally if you only expect one result to be found (perhaps searching a unique column) you can
call `FindFirst()` instead, again passing a filter:

~~~ php
$emptyBucket = CoalBucket::FindFirst( new Equals( "LumpsOfCoal", 0 ) );
~~~

If more than one match is found, only the first is returned. If no matches are found a
RecordNotFoundException is thrown.

Where common searches will be done the best pattern is to create additional methods to wrap
the `FindFirst()` method:

~~~ php
class User extends Model
{
    /// ....

    public static function FromEmail( $email )
    {
        return self::FindFirst( new Equals( "Email", $email ) );
    }

    /// ....
}
~~~

Code using the method is much easier to read and rewriting find commands is avoided.

## Saving an existing record

To update the record in a back end data store simply call the 'Save' method of a model object:

~~~ php
// Instantiate coal bucket '3'
$model = new CoalBucket( 3 );
$model->LumpsOfCoal++;
// Update the database
$model->Save();
~~~

## Deleting a record

Simply call `Delete()`

~~~ php
$model->Delete();
~~~

Note that this removes the object from the back end repository and from the local cache, so if you
try to use an existing collection that previously contained the object, you might get unpredictable
results.

## Tracking Changes

The model class keeps track of what is changing.

* Call `HasChanged()` to determine if the model data has changed since the last change snapshot was
taken.
* Call `TakeChangeSnapshot()` to capture the current model data and use that as its base to compare
with.
* An observer or the model itself can receive notifications when properties in the model are changed. See
[Model Events](events)

## Exporting and Importing Data

On occasion you need to move model data in and out of the model in bulk

* Call `ExportRawData()` to export the underlying model data as an associative array. Magical
getters are not consulted.
* Call `ImportRawData()` to import an associative array directly into the underlying model data. The
model data is
  replaced, not merged. Magical setters are not consulted. The protected function `OnDataImported()`
is called after the
  import.

Often you need a representation of a model that is for public consumption, whether that be an API
end point or simple
serialization (where you can't be sure the data won't be inspected or tampered with). Necessarily we
need to define
which properties should be available for public export. You do this by overriding the
`GetPublicPropertyList()` method
and simply return an array of properties names. This can include the names of computed properties
aswell.

This list of public properties controls two methods:

* `ExportPublicData()` exports the values (if they exist) of all public properties
* `SerializeModelDataAsJson()` takes the response from ExportPublicData() and encodes it as a json
string.

## Sanitising Model Data

Often it's appropriate to take various actions when a model is being saved:

* Populating columns that are built from other columns in the model to save time searching e.g.
Formatting an OrderID into an OrderNumber column
* Populating foreign keys that allow for faster searching by reducing the number of joins e.g.
automatically adding the AddressID to an Order by copying it from the Customer
* Updating balance or outstanding amount columns on a header model when the children are saved

To do this simply override one of two methods: `BeforeSave()` or `AfterSave()`. `BeforeSave()` is
called before the repository is given the model. This may mean that you dealing with a new model so
you need to fence appropriately. `AfterSave()` is called after the repository has been given the
model so you should be guaranteed to have a unique identifier at that point.

~~~
Note: if you are calling `Save()` from within these methods be aware that you can easily end up in
an infinite loop. All calls to `Save()` from these methods should be fenced with an if statement so
that they only occur once.
~~~

## Advanced Topics

* [Relationships between models](relationships)
* [Model Validation](validation)
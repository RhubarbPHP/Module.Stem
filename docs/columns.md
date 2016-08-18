Columns
==============================

A Column object describes the type of data held by a property of a Model object. There are two types of
column; generic columns and repository specific columns.

## Generic Columns

A generic column translates data coming into the model from user code and out of the model to user code.
For example the Boolean column will convert all data passed to it into a boolean and returns a boolean
when asked for the value back:

``` php
$customer->Enabled = 1;

var_dump( $customer->Enabled ); // Accessing Enabled returns bool(true) not int(1)
```

This translation provides you a guarantee that your column will always return data of the correct type.
A more significant example would be that of dates and times. Let's use the example of the generic DateTime
column type:

``` php
$customer->LastOrderDate = "now";
var_dump( $customer->LastOrderDate ); // object(RhubarbDateTime)

$customer->LastOrderDate = "2015-06-01";
var_dump( $customer->LastOrderDate ); // object(RhubarbDateTime)

$customer->LastOrderDate = new DateTime( "yesterday" );
var_dump( $customer->LastOrderDate ); // object(RhubarbDateTime)
```

No matter what type of value `LastOrderDate` is set to, the return value is always a `RhubarbDateTime`.

### List of Generic Column Types

Stem provides a standard set of column types most of which work with all repositories and are easily
understood.

Column              | Description
--------------------|------------
Integer             |Stores whole numbers
FloatColumn               |Stores floating point numbers
Decimal             |Stores decimal numbers with a fixed range of decimal places
Money               |A Decimal column hard coded to 2 decimal places
StringColumn              |Stores text data with a configurable maximum length
EncryptedString     |Stores encrypted text data
LongString          |Stores text data with an unlimited length
DateTime            |Stores date time data
Date                |Stores dates
Time                |Stores times
Boolean             |Stores true and false
AutoIncrement       |Essentially an integer column but repository specific implementations might provide
                    |auto incrementing functionality
ForeignKey          |Essentially an integer column but repository specific implementations might add an index
CommaSeperatedList  |Presents as an array to your user code but stores into a comma separated string in the
                    |repository
Json                |Stores any kind of array or object within the model but stores as a serialised string
                    |within the repository.

All of these column objects are in the `\Rhubarb\Stem\Schema\Columns` namespace.

## Repository Specific Columns

In addition to transforming data on the way into the model from user code and back out again, a Repository
specific column may be needed to transform data from the model into a raw format suitable for storing in
a particular repository.

For example the `DateTime` column stores internally as a `RhubarbDateTime` object however if we want to use
that column in a model connected to a MySQL database the date should be transmitted as '2015-06-01'. Likewise
if the data is fetched from MySQL it must be transformed back into a RhubarbDateTime object.

**Repository specific** columns can achieve this.

In addition a repository specific column can also advise Stem how to create the relevant schema for the
column's settings in the repository model container. Again in our `DateTime` example the `MySqlDateTime`
column knows that the defintion SQL for a DateTime column is

~~~
`LastOrderDate` datetime not null default(0000-00-00 00:00:00)
~~~

Most generic column types will have an equivilant repository specific implementation for the repository you
are using.

## Generic vs Specific columns - which should I use?

If Repository specific columns are needed to control the repository schema and transform data correctly then
I should ignore generic columns and always use the columns specific to my repository right?

**Actually no!** Using repository specific columns ties your model very closely to your chosen repository type.
Should you ever desire to change your data repository you would have to go through every model and change the
column types.

> In general you should **always** use the generic column types unless the column type required is specific only
> to your chosen repository (e.g. a Spacial column type)

In practice if you're using a generic column type the column will be 'upgraded' to a repository specific version
just as an interface with that repository is needed - assuming one can be found (quite simply the class name is
rewritten to include the repository type as part of the class name).

## Creating your own column types

Column types are a great solution to the challenge of allowing a model to present a particular data type (an object
or array etc.) without requiring application code or even the model to perform the translation of that data ready for
storage.

You may also need to create a column type if you are creating a new repository type.

To create a column simply extend the `Column` base class and implement the following methods as appropriate:

**static** fromGenericColumnType( Column $genericColumn )
:   Upgrades a matching generic column type to a repository specific version and returns it.

getDefinition()
:   Should return the schema generation string required to generate a column of this type in the
    repository. e.g. a MySQL alter table SQL for that column.

getTransformIntoModelData()
:   If required this should return a call back function to convert incoming data from user code into the
    correct internal storage format with the model (not the repository). Receives a single argument containing the
    incoming value and should return the transformed value. e.g.

    ``` php
    public function getTransformIntoModelData()
    {
        return function ($data) {
            return new RhubarbDateTime($data);
        };
    }
    ```

getTransformFromModelData()
:   If required this should return a call back function to convert data from the internal stored model data suitable
    for use by user code. Receives a single argument containing the internally held value and should return the
    transformed value. Essentially the reverse of `getTransformIntoModelData()` however it is seldom used as
    the most common strategy is to use `getTransformIntoModelData()` to store data in the format you want it
    to be return back in.

getTransformIntoRepository()
:   If required this should return a call back function to convert data from the internal stored model into a value
    suitable for transmitting to the repository. Receives a single argument containing all the internally held model
    data and should return either a single value representing the transformed value for this column **OR** an array of
    transformed values if using multiple storage columns.

getTranformFromRepository()
:   If required this should return a call back function to convert data from the repository into the format
    internally stored in the model. Receives a single argument containing the raw repository data for the model
    and should return a single transformed value for the column.

getStorageColumns()
:   Returns an array of lower level columns used to store the column's data. This allows two neat tricks.

    1. Some generic column types perform useful data transformations making our life as a developer a lot easier,
    for example the Json column. Ultimately the Json column stores it's data in a simple string column type.
    To avoid having to create a specific column for Json every repository type, instead the generic column type
    can elect a different column type to handle the storage of it's data. Json returns an instance of the
    `LongString()` column which is handled already by most repositories so it's instantly supported by all
    repositories without a lot of duplication. For example here is the implementation of this function for the
    Json class:

    ``` php
    public function getStorageColumns()
    {
        return [ new LongString($this->columnName) ];
    }
    ```
    2. A column can be a composite column, where the column itself doesn't exist in the database but instead
    stores it's data in a number of other fields. For example imagine an Address column. It might store it's
    values in AddressLine1, City, Region and Country fields instead of a single Address field. In cases like this
    you can return multiple storage columns:

    ``` php
    public function getStorageColumns()
    {
        return
        [
            new StringColumn("AddressLine1",50),
            new StringColumn("City",50),
            new StringColumn("Region",50),
            new StringColumn("Country",50)
        ];
    }
    ```

    Note that this pattern is usually accompanied by getTransformIntoRepository and getTransformFromRepository
    to handle the explosion and implosion of the component parts into one data structure. Also note that
    while this works, it's more usual to see the name of the column included in the naming of the storage elements
    e.g.

    ``` php
    public function getStorageColumns()
    {
        return
        [
            new StringColumn($this->columnName."Line1",50),
            new StringColumn($this->columnName."City",50),
            new StringColumn($this->columnName."Region",50),
            new StringColumn($this->columnName."Country",50)
        ];
    }
    ```


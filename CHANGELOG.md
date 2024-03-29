# Change Log

### 1.9.5

* Fixed:  PHP 8 makes PDO::ERRMODE_EXCEPTION the default, meaning the RepositoryStatementException was no longer being
          caught as a PDOException was being thrown. Added a catch for PDOException.

### 1.9.4

* Fixed:  PHP 8 has added supported for named arguments. This can cause problems when using call_user_func_array().

### 1.9.3

* Changed:  A flag added to allow a null value to be passed to the front end instead if being cast to 0. 

### 1.9.2

* Fixed:   Repository timezone can be set in StemSettings

### 1.9.1

* Fixed:   Aliases now fully quoted
### 1.9.0

* Changed: Collections now support per repository optimisation of collection counting. This is implemented
           in MySql as a COUNT(*) on the original query. This results in more queries overall, but in many
           cases greatly increased performance and reduced network traffic.

### 1.8.18

* Changed: Further fixes for phpDocumentor

### 1.8.17

* Changed: Had to remove constraint on phpDocumentor

### 1.8.16

* Added:   Compatibility with phpDocumentor 2.0 for document models command.

### 1.8.15

* Added:   Forward compatibility for later version of symfony Console

### 1.8.14

* Fixed:   ensure that there is an alias mapping in place for fields that have been pulled up through multiple
           intersections 

### 1.8.13

* Fixed:   includeBulk flag passed to prereq seeders

### 1.8.12

* Fixed:   implode param order to remove php 7.4 warning

### 1.8.11

* Added:   OneOfCollection Filter
* Added:   MySql repository support for hydrating a subset of a model's columns

### 1.8.10

* Fixed:   Sort by Aggregate now works in MySql on the outer collection

### 1.8.9

* Fixed:   Aggregates can now be sorted on

### 1.8.8

* Fixed:   Custom sorts now invalidate collections
* Added:   MySqlRandom Sort

### 1.8.7

* Fixed:   Added value inspection to determine type when unit testing and no column
           type information available on aggregate filters.

### 1.8.6

* Fixed:   Pullups from aggregates can now be used in where clauses

### 1.8.5

* Changed: MySql can now group/join by intersected columns using their name or aliased name

### 1.8.4

* Added:   beforeScenario and afterScenario hooks for improving scenario seeding

### 1.8.3

* Added:   Some pretty colours when showing seeding titles.

### 1.8.2

* Added:   Bulk flag added to seed command. BulkScenario added.
* Changed: Truncation of data turned off by default when seeding.
* Added:   getPrerequisiteSeeders() can now return class names instead of seeding objects.

### 1.8.1

* Fixed:   Stopped SchemaCommandTrait from trying to use the settings folder which might not exist.

### 1.8.0

* Changed: Removed automated schema updating when in developer mode.

### 1.7.10

* Fixed:   A many-2-many relationship didn't correctly define both 'Raw' relationships if
           the same model was on both ends of the relationship

### 1.7.9

* Changed: MysqlEnumColumn now supports null values.

### 1.7.8

* Added:   Obliterate -o and Force -f options added to SeedDemoDataCommand
* Added:   ScenarioSeeders can now override `getPreRequisiteSeeders` to return a list of other seeders to run first

### 1.7.7

* Added:   Custom sort objects
* Added:   clearSortS() added to Collection

### 1.7.6

* Changed: MysqlSetColumn supports null and empty.

### 1.7.5

* Changed: Don't serialize propertyCache

### 1.7.4

* Fixed:   Correct column type not used when filtering if filter applied to a pulled up column

### 1.7.3

* Added:   ScenarioDataSeeder for seeding scenarios
* Added:   Example usage of ScenarioDataSeeder in the docs

### 1.7.2

* Added:   DescribedDemoDataSeederInterface for seeders that can self describe to the terminal the scenarios they've configured

### 1.7.1

* Fixed:   Stem now filters on composite columns in SQL for MySQL

### 1.7.0

* Added:   Seeder improved to allow listing of possible seeders and execution of a single seeder.

### 1.6.1

* Changed: When creating connection, use DB time_zone rather than system_time_zone to set repositoryDateTime setting.

### 1.6

* Changed:  Only check and update schemas in developer mode

### 1.5.11

* Added:    PDO connection options to Mysql Repository 

### 1.5.10

* Added:    Flag to disable casting in integer column - a temporary measure for backwards compatibility. 

### 1.5.9

* Fixed:    Fenced added to prevent warning thrown by de-duping rows which have no augmentation data

### 1.5.8

* Changed:  Complying with Log::error() spec 

### 1.5.7

* Fixed:    Bug where `$modelClassName` was accessed before it was initialised. 

### 1.5.6

* Changed:  Cache location for schema versions nows under TEMP_DIR

### 1.5.5

* Changed:  MySqlJsonColumn must now be used specifically in schema to get "JSON" data type in MySql

### 1.5.4

* Fixed:    Read/Write splitting broke everything not using splitting...

### 1.5.3

* Fixed:   LSB issue with pdo repository
* Changed: write stickness setting defaults to true

### 1.5.2

* Added:    Read/Write connection splitting support
* Added:    Read only connection stem settings

### 1.5.1

* Fixed:    MysqlJsonColumn now supports $decodeAsArrays

### 1.5.0

* Removed:  ModelLoginProvider no longer has a login method - it only includes support for knowing that a
            particular model is 'logged in'. As nearly all login providers extend the scaffolded version
            (where this has moved to) this should not be a breaking change for anybody.           
* Fixed:    JSON unit tests all passing
* Fixed:    Bug where offline filtering of intersections that joined 1 to many would not return the
            expected results.

### 1.4.6

* Added:    IntegerColumn, FloatColumn and BooleanColumn all format repository values into the appropriate datatypes

### 1.4.5

* Added:    Adding a new ListContains filter to allow for Array filtering

### 1.4.4

* Added:    Adding support for MySql Literal filter

### 1.4.3

* Fixed:    MySqlSetColumn values need to be transformed when going in, and out of the database.

### 1.4.2

* Fixed:    AllWordsGroup needs to trim the search before splitting, otherwise a leading or trailing space will end up with an empty word in the array, which will match anything

### 1.4.1

* Fixed:    When attempting to filter on a collection with multiple columns of the same name, the filter could only be applied to the last one. It now uses aliases to allow you to specify the correct column by giving it a unique alias and referring to that.

### 1.4.0

* Added:    Charset support for MySQL Repository

### 1.3.30

* Fixed:    disableRanging() wasn't actually working....

### 1.3.29

* Fixed:    Fixed intersection bug with multiple joins to the same collection

### 1.3.28

* Fixed:    Fixed bug with filtering on pulled up columns not transforming values correctly.

### 1.3.27

* Fixed:    Fixed bug with batchUpdate where SQL statements with AND/OR groups weren't correctly bracketed

### 1.3.26

* Fixed:    Issue with intersections and autohydration in combination with composite columns

### 1.3.25

* Fixed:    sorting on columns clashing with intersected columns would not sort correctly

### 1.3.24

* Fixed:    calculateAggregates on an intersection now removes groups

### 1.3.23

* Fixed:    Updated Codeception version

### 1.3.22

* Fixed:    Issue with the OneOf Filter not being applied to the Query

### 1.3.21

* Fixed:    Schema version comparison bug when creating a schema

### 1.3.20

* Fix:      Properly supported filtering on aggregates (without intersections) in MySql and offline.

### 1.3.19

* Change:   Now simple intersections and joins to unfiltered, unsorted, ungrouped, unjoined collections go through as simple joins to the other table.    
* Added:    setEnableTruncating to SeedDemoDataCommand to allow auto-truncation of tables to be disabled

### 1.3.18

* Fixed:    Money and decimal fields are now numerically sorted by the collection
* Fixed:    Limited ranges can be correctly iterated using the cursor (instead of the repository)

### 1.3.17

* Added:    Model schemas can be created from a specific repo determined by via Repository::getModelSchemaRepoClassName()

### 1.3.16

* Fixed:    Offline repo stores identifier value regardless of column type

### 1.3.15

* Fixed:    Placeholders now work with pull ups

### 1.3.14

* Fixed:    Fixed unit tests - MySqlFilterTrait final static method made instance and non final
* Fixed:    Added MySqlFloatColumn to support the FloatColumn class

### 1.3.13

* Fixed:    MySql Timezone handling fix introduced issue with SaasMySqlRepository

### 1.3.12

* Fixed:    MySql Timezone handling - Ensuring repo sets timezone from MySql database before Column uses it

### 1.3.11

* Added:    Support for batch updating with intersections

### 1.3.10

* Fixed:    ModelCollectionUrlHandler now uses Model::all() so that base filters are respected

### 1.3.9

* Fixed:    Aggregates of nested intersections now populates additional column data on collections correctly. 

### 1.3.8

* Added:    Adding a JSON contains filter and removing a static final method

### 1.3.7

* Change:   Speed improvement by using isset instead of in_array

### 1.3.6

* Fixed:    Pull ups of columns now respect types when filtering in MySql
* Added:    Support for aggregation of pull ups.

### 1.3.5

* Added:    BinaryDataColumn (mysql longblob)
* Fixed:    Collection column aliasing no longer get progressively slower with each unique reference used
* Fixed:    Offline repo correctly reduces grouped collections (when not grouping due to intersection)

### 1.3.4

* Fixed:	Fixed bug with ColumnIntersectsCollection doubling up intersections if the collection was modified and reused.

### 1.3.3

* Fixed:	Filters creating intersections caused collections with a missing group resulting in multiple rows.

### 1.3.2

* Added:	Collection::clearFilter() to allow removal of a filter on a collection.

### 1.3.1

* Fixed:    MysqlCount caused a PHP warning due to a function signature incompatibility

### 1.3.0

* Change:   Overhauled how filters and aggregates find their repository specific versions.
* Fixed:    Issue where repository filters would still cause iteration of the collection.

### 1.2.4

* Fixed:    Fix for MySqlOneOf when no options supplied

### 1.2.3

* Change:   Changed MySqlLongStringColumn to store as an SQL 'LONGTEXT' column rather than a shorter 'TEXT' column
* Fixed:    ColumnIntersectsCollection didn't work if not using the unique identifer on both sides of the intersection
* Fixed:    ColumnIntersectsCollection wasn't 'not'able.

### 1.2.2

* Fixed:    Fixed missing transforms when using the MysqlCursor!
* Fixed:    Collection::calculateAggregates() returns nulls if called on an empty collection 

### 1.2.1

* Fixed:    DateTimeColumn returns a DateTime object, not a Date object, which loses the time component 

### 1.2.0

* Added:    Much better repository support for complicated joins, aggregates and filters through intersectWith() and
            joinWith()
* Added:    Concept of cursors to optimise iteration performance with repository connected collections
* Added:    Collection::all() as an alias of find() with no arguments.
* Added:    RepositoryCollection

### 1.1.1

* Change:   Group now accepts passing in of filters as parameters as well as in an array.
* Change:   Model::find() now accepts multiple filters as parameters -> will make an 'And' Group of the filters.
* Change:   Collection::filter() now accepts multiple filters as parameters -> will make an 'And' Group of the filters.
* Fix:      Fixed issue with deleteRepositories not accessing static scope

### 1.1.0

* Added:    New ColumnIntersectsCollection filter type.
* Change:   Model cache for repositories and relationships is no in application shared data.
* Fix:      Unit test fixes
* Fix:      Composer bumps to fix ant build.

### 1.0.1

* Change:   Collections that use join queries no longer provider multiple instances of a model during iteration.
* Fixed:    Bug with Filter::detectPlaceHolder
* Change:   Some documentation updates
* Fixed:    EndsWith filter fixed
* Added:    stem:create-model custard command
* Added:    CommaSeparatedColumn supports enclosure with additional commas
* Change:   Relationship declaration methods are now protected so the can be called directly from the solution schema
* Fixed:    Cache path now uses application root dir correctly.

### 1.0.0

* Added:    Added codeception
* Change:   PHP7 support by suffixing column class names with "Column"
* Added:    Added a changelog
* Fixed:    Fixed failing tests
* Added:    Added build.xml for Jenkins CI
* Added:    Added generic form of Index class which becomes specialised into MySql versions with the schema, the same way Columns work
* Added:    Project and repository timezone defaults added to StemSettings

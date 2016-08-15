# Change Log

### 1.2.x

* Change:   Changed MySqlLongStringColumn to store as an SQL 'LONGTEXT' column rather than a shorter 'TEXT' column

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

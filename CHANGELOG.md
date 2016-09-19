# Change Log

### 1.3.x

* Fixed:    Offline repo correctly reduces grouped collections (when not grouping due to intersection)

### 1.3.4

* Fixed:	    Fixed bug with ColumnIntersectsCollection doubling up intersections if the collection was modified and reused.

### 1.3.3

* Fixed:	    Filters creating intersections caused collections with a missing group resulting in multiple rows.

### 1.3.2

* Added:	    Collection::clearFilter() to allow removal of a filter on a collection.

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

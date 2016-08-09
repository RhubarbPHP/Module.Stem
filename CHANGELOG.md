# Change Log

### 1.1.x

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

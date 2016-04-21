### 1.0.x

* FIXED:    Bug with Filter::detectPlaceHolder
* CHANGE:   Some documentation updates
* FIXED:    EndsWith filter fixed
* ADDED:    stem:create-model custard command
* ADDED:    CommaSeparatedColumn supports enclosure with additional commas
* CHANGE:   Relationship declaration methods are now protected so the can be called directly from the solution schema

### 1.0.0

* ADDED:    Added codeception
* CHANGE:   PHP7 support by suffixing column class names with "Column"
* ADDED:    Added a changelog
* FIXED:    Fixed failing tests
* ADDED:    Added build.xml for Jenkins CI
* ADDED:    Added generic form of Index class which becomes specialised into MySql versions with the schema, the same way Columns work
* ADDED:    Project and repository timezone defaults added to StemSettings
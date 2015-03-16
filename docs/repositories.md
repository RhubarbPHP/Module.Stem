Data Repositories
===

A repository is responsible for the following:

* Sourcing data for model objects
* Storing data from model objects
* Implementing data store support for Filters where possible.
* Caching data
* Providing schema information
* Updating it schemas where relevant.

Repositories extend the `Repository` class (`Gcd\Core\Modelling\Repositories`). The repositories provided within the
modelling module itself are Offline, Pdo and MySql.

The offline repository allows for unit testing by simulating the provision of a unique identifier when an object is
stored.

As objects are fetched and stored the repository will cache the data within it's $cachedObjectData collection. This
allows for unit testing *by design* but also provides a performance boost as frequently used records (within the
lifetime of the script) are only fetched once.

## The default repository

By default new model objects will use the Offline repository unless your application either changes the repository for
each model object individually, or by changing the default repository. To change the default repository you must make a
call to `SetDefaultRepositoryClassName` before any data objects are created.

~~~ php
Gcd\Core\Modelling\Repositories\Repository::SetDefaultRepositoryClassName( "Gcd\Core\Modelling\Repositories\MySql\MySql" );
~~~

A good place for this is in the Initialise() function of your site [Module](/basics/modules)


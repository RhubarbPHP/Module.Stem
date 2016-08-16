Migrating Schemas
===================================

The `stem:update-schemas` command forcibly checks all models in a schema against the
existing schema definition in the repository and migrations the schema if required.

``` bash
bin/custard stem:update-schemas
```

The command needs access to the repository so needs to be run in a context where the
stem settings provide the correct details (e.g. ran from inside a vagrant box or docker
container).

The command will prompt you to select which schema you want to check.

Note, this requires setting up [custard](/manual/rhubarb/custard/) and ensuring you've
added the StemModule as a requirement in your application's `getModules()` function.
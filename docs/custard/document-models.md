Updating DocBlock comments
===================================

The `stem:document-models` command evaluates the schema of all models in a chosen
solution schema. It updates the DocBlock comments above the class to ensure that
each column in the schemas is listed as an @property allowing your IDE to infer
the correct type for each column property.

Relationships are also evaluated and added as comments.

``` bash
bin/custard stem:document-models
```

The command does not need access to the repository so can be run in any environment.

The command will prompt you to select which solution schema you want to update.

Note, this requires setting up [custard](/manual/rhubarb/custard/) and ensuring you've
added the StemModule as a requirement in your application's `getModules()` function.
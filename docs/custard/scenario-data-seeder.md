Scenario Data Seeding Example usage:
====================================

> This is useful for seeding data to a colleague without needing to clear their entire database 

* In your Application class, you will need to add the following line to `getCustardCommands()`
```php
SeedDemoDataCommand::registerDemoDataSeeder(new MyDescribedSeeder());
```
* Create your class 
```php
class MyDescribedDataSeeder extends DescribedDataSeeder
{
    /**
     * @return Scenario[]
     */
    function getScenarios(): array
    {
        return [
            new Scenario(
                "My Example Scenario To Seed",
                function (ScenarioDescription $scenarioDescription) {
                $user = new SystemUser();
                $user->Username = "Rhubarb";
                $user->setPassword("Custard");
                $user->save();
        
                    $scenarioDescription
                        ->writeLine("A user has been created <bold>Username: </bold> Rhubarb <bold>, Password:</bold> Custard ")
                        ->writeLine("Log in at localhost:8080/login/")
                }
            )
        ];
    }
}
```

Run the command 

```sh
vendor/bin/custard MyDescribedDataSeeder

Running scenario 1: My Example Scenario To Seed
   A user has been created Username: Rhubarb, Password: Custard
   Log in at localhost:8080/login/
```

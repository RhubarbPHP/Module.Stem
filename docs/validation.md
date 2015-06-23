Consistency Validation
===

Model validation allows you to describe what constitutes a valid model, in other words one which
is consistent with the business rules and architecture of the overall solution. It is strongly
recommended that model validation is applied very early in the development cycle. Because concurrency
violations are highlighted to the developer rather than entering the database many careless mistakes
can be avoided. The categories of bug that validation checking can prevent often cause many wasted
hours trying to correct or patch data in the database from logs, backups or through careful analysis
of the bug context.

Consistency validation is also an essential protection if using the RestApi module and ModelRestResource
objects. As these receive data directly from potential malicious users the consistency validation rules
should form a key part of the application's defense.

## Implementing validation rules

Simply override the `GetConsistencyValidationErrors()` method in your model classes. This method
should return an array of any validation errors, and an empty array if there are none:

``` php
    protected function GetConsistencyValidationErrors()
	{
		$errors = [];

		if ( !$this->CompanyName )
		{
			$errors[ "CompanyName" ] = "Company name must be supplied";
		}

		return $errors;
	}
```

There is no limit to the number of rules you can apply and all the validation rules should be
evaluated so that developers and API users can quickly correct all outstanding issues.

## Testing consistency

The consistency of the model will be tested automatically when you call `Save()`. If validation
errors exist, the model will not be saved and a `ModelConsistencyValidationException` will be thrown.
You can call `GetErrors()` on this exception object to get the array of exception messages.

``` php
try
{
    $company->Save();
}
catch( ModelConsistencyValidationException $er )
{
    $errors = $er->GetErrors();
}
```

If you want to test the model consistency before calling `Save()` you can simply call `IsConsistent()`.
This method takes an optional boolean to control whether it simply returns true or false or
throws the `ModelConsistencyValidationException` exception. The default is to throw the exception.

``` php
// Approach 1 - let it throw if inconsistent
try
{
    $company->IsConsistent();
}
catch( ModelConsistencyValidationException $er )
{
    $errors = $er->GetErrors();
}

// Approach 2 - we don't care about errors - just give us a boolean.
$consistent = $company->IsConsistent( false );

if ( !$consistent )
{
    // ...
}
```

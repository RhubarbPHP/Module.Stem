Data Decorators
===

Models deal strictly in storage of data and the application of business rules. View level code however has a need
to use formatted and sometimes decorated versions of the application data. These 'decorations' are often required
time and time again, however they should not be added to the model as they are strictly speaking in the domain of
the view. Data Decorators allow us to house commonly used formatting and decorating instructions in a container
which can be discovered and used by built in and user defined views.

`DataDecorator` acts both as a base class for all decorators and also as a factory for generating the appropriate
decorator a particular model. The factory pattern is used as we want to ensure only once instance of a decorator
is in use for any given model.

Formatting and decorating are both extremely similar but in fact a formatter can decorate and a decorator can
format - however the developer should try and keep the two separate. Formatting involves taking a value and
transforming it into a different representation of the same value. e.g. True to "Yes", 2001-01-1 to "1st Jan 2001",
0 to "0.00". Decorating involves embellishing the value with additional content e.g. Complete to
&lt;img src="/images/ok.png" alt="Complete" /&gt;

## Using a DataDecorator

To use a decorator simply call the static `DataDecorator::GetDecoratorForModel( Model $model )` function call:

~~~ php
$decorator = DataDecorator::GetDecoratorForModel( $coalBucket );
~~~

Note that this call may return false if there is no decorator available for a particular model.

Once you have a decorator you can simply access properties on it much as you would for the underlying model.
Properties that have been configured to have formats or decoration will be formatted and decorated as appropriate.

~~~ php
$decorator = DataDecorator::GetDecoratorForModel( $coalBucket );

print $decorator->IsFireRisk();     // Prints "No"
~~~

Classes provided by Core like the Table presenter should make use of decorators by default.

## Applying a DataDecorator

Decorators need assigned to individual models as the decorating and formatting instructions are applied at a
column and type level. However it is also possible and common to target a base class with a shared common
decorator to get a base level of data formatting to solve in one stroke a clients formatting requirements. There is
in fact a "CommonDataDecorator" provided in Core to format some of the most basic things.

To apply a decorator you need to call `DataDecorator::RegisterDecoratorClass( $decoratorClassName, $modelClassName );`

~~~php
DataDecorator::RegisterDecoratorClass( "Gcd\Core\Modelling\Decorators\CommonDataDecorator", "Gcd\Core\Modelling\Models\Model" );
~~~

You can however only register one decorator for a model. In the case that a decorator is also available for a parent
class, the most specific class matched decorator is used.

## Creating a DataDecorator

Simply extend the most appropriate base decorator and then define the following methods:

~~~ php
class MyDataDecorator extends CommonDataDecorator
{
    protected function RegisterColumnDefinitions()
    {
        $this->AddColumnFormatter( "RecordID", function( Model $model, $value )
        {
            return str_pad( $value, 5, '0', STR_PAD_LEFT );
        } );

        $this->AddColumnDecorator( "Email", function( Model $model, $value )
        {
            return "<a href=\"mailto: $value\">".$value."</a>";
        } );
    }

    protected function RegisterTypeDefinitions()
    {
        $this->AddTypeFormatter( "Gcd\Core\Modelling\Schema\Columns\DateTime", function( Model $model, $value )
        {
            return $value->format( "jS F Y H:i" );
        } );
    }
}
~~~

There are two types of definition; a column definition and a type definition. A type definition applies the
transformation to all columns matching or being a sub class of a given schema column type. A column definition
only applies to a specific column name.

Transformations are registered by call the `AddColumnFormatter`, `AddColumnDecorator`, `AddTypeFormatter` or
`AddTypeDecorator` methods as appropriate. The transformation itself is handled by an closure which is padded both
the value being formatted or decorated and the full model object. The model object is sometimes required by
decorators which need to apply a template involving other columns of the model.
Creating Your Own Filter
===

In some instances it can be useful to create your own filter for a
project. In nearly all cases this should be an extension of the
AndGroup or OrGroup class which 'auto' populates it's list of
filters. Building a filter using groups of simpler filters maximises
compatibility with Repositories ensuring good performance.

## Group based filters

Imagine your project had a Contact, Order and Invoice models that all
carried a set of address fields. Perhaps in your project you
keep finding a requirement to select models that have a valid address,
i.e. AddressLine1, City and Country all populated.

To avoid having to create a group of Not filters each time this
is needed you could make your own filter that expressed this behaviour.

``` php
class AddressValid extends AndGroup
{
    public function __construct()
    {
        parent::__construct(
            new Not(new Equals("AddressLine1", ""),
            new Not(new Equals("City", ""),
            new Not(new Equals("Country", "")
        );
    }
}

$contacts = Contact::find(new AddressValid());
$invoices = Invoice::find(new AddressValid());
$orders = Order::find(new AddressValid());
```

## Custom filters

If you need a more complex calculation that can't be easily constructed
through basic filters and groups you may need to build your own
custom filter. All filter objects extend the Filter base class so your
first task is to extend it and then implement the `evaluate()`
function. `evaluate()` should return **true** if you calculate the
model in question should be removed (filtered) from the collection.

``` php
class StockMonths extends Filter
{
    public function evaluate(Model $model)
    {
        // Remove models where stock levels indicate it won't last another
        // month.
        if (($model->AverageUsagePerMonth / $model->StockLevel) < 1) {
            return true;
        } else {
            return false;
        }
    }
}
```

It's important to bear in mind however that a custom filter will have
no repository support and will involve iterating over all the 
models in the collection to evaluate your filter.
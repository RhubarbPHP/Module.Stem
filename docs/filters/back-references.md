Value back references
=====================

Sometimes instead of filtering using literal values you want to filter
using the value of other columns in a model. Stem supports this by way
of a placeholder syntax:

```
@{ColumnName}
```

Simply use this in place of a literal value to have this used instead.

For example to select customer records where their outstanding balance
is greater than their credit limit you could use a filter like this:

``` php
$overLimit = Customer::find(new GreaterThan("OutstandingBalance", "@{CreditLimit}"));
```

This occurs most frequently when performing
[intersections](../intersections), pulling up an
[aggregate](../aggregates) value and then filter. In the above example
we supposed that the OutstandingBalance was calculated into a simple
column on the customer model. What if it wasn't and had to be calculated
on the fly:

``` php
$overLimit = Customer::all()
    ->intersectWith(
        Invoice::all()
            // Aggregate the balance of invoices for the customer
            ->addAggregateColumn(new Sum("OutstandingBalance")),
        "CustomerID",
        "CustomerID",
        // Pull up the aggregate value into the customer collection
        [ "SumOfOutstandingBalance" ])
    // Filter on the customer collection back referencing the aggregate value.
    ->filter(new LessThan("CreditLimit", "@{SumOfOutstandingBalance}"));
```
Aggregates
===

Aggregates provide a way to calculate aggregate values on a collection (e.g. Sum, Count, Average
etc.) while still allowing for repository specific optimisations.

Consider the scenario where you have a collection of invoices and you want to display a list of the
invoices with the number of lines attached to the invoice. One solution would be to create a getter
method in the Invoice model that returns the size of the `Lines` relationship collection. However if the list
of invoices was large (for example several thousand) this would result in several thousand round trips to the
database **and** the populating in memory of many more thousands of invoice lines just to count them.

Aggregates are small classes that can perform the computation.

The syntax for creating an aggregate is extremely simple:

``` php
$sum = new Sum( "FieldToSum" );
```

> Just like filters there will be repository specific versions of aggregates that **should not** be
> instantiated directly.

There are two scenarios where aggregates are used.

### Scenario 1: Aggregating all the rows of a collection.

For example you might have a list of BankAccount models and you want to calculate the total balance
of all accounts:

``` php
$collection = BankAccount::all();
list($totalBalance) = $collection->calculateAggregates(new Sum("Balance"));
```

The slightly arcane `list` function is used here as calculateAggregates returns an array
of results, one for each aggregate passed to it. The results are returned in the same order as the aggregates
are passed. This allows the repository to execute a number of aggregates at the same time to maximise the optimisation.

``` php
$collection = BankAccount::all();
// Calculate the total of all balances, and count the bank accounts too.
list($totalBalance, $count) = $collection->calculateAggregates(
                                    new Sum("Balance"),
                                    new Count("Accounts"));
```

### Scenario 2: Aggregating columns involved in an [intersection](intersections).

Let's take our invoice example from the introduction:

``` php
$invoices = Invoice::all();
$invoices->addAggregateColumn(new Count("InvoiceLines.InvoiceLineID"));

print $invoices[0][ "CountOfInvoiceLinesInvoiceLineID" ];
```

Here we are actually registering a new column to be created using the result of the aggregate function on the
nested collection of invoice lines. The column name in our resultant model becomes SumOf, CountOf etc. followed by
the original column description without any full stops. To provide your own alias name, simply pass the second
argument of the aggregate constructor:

``` php
new Sum("Balance", "TotalBankBalances")
```

## Available Aggregates

Currently the following aggregates exist:

* Sum (creates SumOf[x])
* Count (creates CountOf[x]): Counts all the rows of the collection or property
* CountDistinct (creates DistinctCountOf[x]): Counts only the unique values of the field in the
collection
* Average (creates AverageOf[x])
* Min (creates MinOf[x])
* Max (creates MaxOf[x])
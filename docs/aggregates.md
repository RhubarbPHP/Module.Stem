Aggregates
===

Aggregates provide a way to calculate aggregate functions on a collection (e.g. Sum, Count, Average
etc.) while allowing for repository specific optimisations.

Consider the scenario where you have a collection of invoices and you want to display a list of the
invoices with a column that shows the number of invoice lines attached to the invoice. One solution
would be to create a Getter method in the Invoice model that returns the size of the `Lines`
relationship. However if the list of invoices was large (for example several thousand) this would
result in several thousand round trips to the database **and** the populating in memory of many more
thousands of invoice lines just to count them.

Aggregates are small classes that describe the computation needing done with provision for allowing
a Repository to do the same calculation on the database server before transmitting the data.

The syntax for creating an aggregate is extremely simple:

~~~ php
$sum = new Sum( "FieldToSum" );
~~~

Note that just like filters there will be repository specific versions of aggregates that **should
not** be instantiated directly!

There are two occasions where aggregates are used - both subtly different.

### Scenario 1: Aggregating a field on a simple list to return the aggregate over all rows.

For example you might have a list of BankAccount models and you want to calculate the total balance
of all accounts:

~~~ php
$collection = new Collection( "BankAccount" );
list( $totalBalance ) = $collection->CalculateAggregates( new Sum( "Balance" ) );
~~~

The slightly arcane `list( $totalBalance )` is used here as this function actually returns an array
of results. This is because the function actually supports being passed multiple aggregates. The
results are returned in the same order as the aggregates are passed. This allows the repository to
execute a number of aggregates at the same time to maximise the optimisation.

### Scenario 2: Aggregating a field on a one to many relationships to aggregate the rows of the
relationship

Let's take our invoice example from the introduction:

~~~ php
$invoices = new Collection( "Invoice" );
$invoices->AddAggregateColumn( new Count( "InvoiceLines.InvoiceLineID" ) );

print $invoices[0][ "CountOfInvoiceLinesInvoiceLineID" ];
~~~

In this scenario we are actually registering new columns to be created using the result of the
aggregate function. The column name becomes SumOf, CountOf etc. followed by the original column
description without any full stops.

## Available Aggregates

Currently the following aggregates exist:

* Sum (creates SumOf[x])
* Count (creates CountOf[x]): Counts all the rows of the collection or property
* CountDistinct (creates DistinctCountOf[x]): Counts only the unique values of the field in the
collection
* Average (creates AverageOf[x])
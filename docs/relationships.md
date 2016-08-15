Relationships
=============

In a traditional database you rely on relationships between the data stored in individual tables, usually
where the same field exists in both tables. You will probably be familiar with the concepts of one-to-many,
one-to-one and many-to-many relationships.

Stem replicates the same ideas through it's own concept of relationships.

Defining the relationships between models allows you to 'navigate' between them in code through a
simple object property like notation.

``` php
// Get the contact's company record by fetching the Company model through the relationship
$company = $contact->Company;

// Display the company's accountant name stored in the Accountant model.
print $company->Accountant->Name;
```

A relationships might give you a related model (as above) or a collection of models depending on the
relationship type:

``` php
// Get all the employees of the company:
$employees = $company->Employees;

foreach($employees as $employee) {
    $employee->emailPayslip();
}
```

In order to use relationships in this way you need to declare what [solution schema](models-and-schemas)
models are related and how.

## Declaring Relationships

The correct place to declare relationships is by overriding the `defineRelationships` function of the
your application's solution schema class.

There are three methods you can call from here to define the relationships in your schema:

declareOneToManyRelationships
:	Declares one or more "one-to-many" relationships and the corresponding reverse "one-to-one"
relationship

declareOneToOneRelationships
:	Declares one or more "one-to-one" relationships and the corresponding reverse "one-to-one"
relationship

declareManyToManyRelationships
:	Declares one or more "many-to-many" relationships and the corresponding reverse "many-to-many"
relationship

### One-to-Many Relationships

The syntax of `declareOneToManyRelationships` and `declareOneToOneRelationships` is the same:

~~~ php
$this->declareOneToManyRelationships([
	"OneModelName" =>
	[
		"CollectionName" => "ManyModelName.OneModelPrimaryKeyNameInManyModel"
	]
]);
~~~

It might be helpful to see this using a familiar example of a relationship between customer and orders:

~~~ php
$this->declareOneToManyRelationships([
	"Customer" =>
	[
		"Orders" => "Order.CustomerID"
	]
]);
~~~

This sets up a property on a Customer model of `->Orders` which returns the collection of Orders for
that customer. It also sets up a property on Order models of `->Customer` which returns the model
for the customer of the order.

This is actually a shorthand form of the declaration but the most commonly used. We're assuming that
the relationship is using the unique identifier (CustomerID) of the Customer model and that the reverse
relationship will have the name "Customer". The previous example is syntactically equivalent to:

~~~ php
$this->declareOneToManyRelationships([
	"Customer.CustomerID" => [
		"Orders" => "Order.CustomerID:Customer"
	]
]);
~~~

The expanded syntax allows for occasions where one table relates to another table several times. For example:

~~~ php
$this->declareOneToManyRelationships([
	"User" => [
		"UpdatedTickets" => "Ticket.LastUpdatedByUserID:LastUpdatedBy",
		"CreatedTickets" => "Ticket.LastCreatedByUserID:LastCreatedBy",
		"AssignedTickets" => "Ticket.AssignedtoUserID:AssignedTo"
	]
]);
~~~

Here we're telling Stem that the Ticket model has three different User relationships; one each for LastUpdatedBy,
LastCreatedBy and AssignedTo using their respective fields. The User model in turn now has three collection
properties "UpdatedTickets", "CreatedTickets", "AssignedTickets".

### Many-to-Many Relationships

Many to many relationships simplify your code by removing the need to 'chain' through the
intermediate model object.

Also items can be added to the collection without having to create the
intermediate row yourself:

~~~ php
$product = new Product( 1 );
$category = new Category( 1 );

$product->Categories->Append( $category );
// OR
$category->Products->Append( $product );
~~~

`declareManyToManyRelationships` uses a slightly different syntax as we must also include the name
of the linking model in the definition.

~~~ php
$this->declareOneToManyRelationships([
	"LeftModelName" =>
	[
		"LeftCollectionName" => "LinkingModelName.LeftModelPrimaryKey_RightModelPrimaryKey.RightModelName:RightCollectionName"
	]
]);
~~~

Again a real example may help:

~~~ php
$this->declareManyToManyRelationships([
	"Product" => [
		"Categories" => "ProductCategory.ProductID_CategoryID.Category:Products" ]
	]
]);
~~~

Here we use the ProductCategory linking model to define a many-to-many relationship between products and categories.

### Defining multiple relationships

The declare functions take an array so you can avoid calling them individually for each relationship.

~~~ php
$this->declareOneToManyRelationships([
    "User" => [
        "Logins" => "LoginHistory.UserID"
        ],
    "Customer" => [
        "Orders" => "Order.CustomerID",
        "Invoices" => "Invoice.CustomerID",
        "Addresses" => "Address.CustomerID"
    ]
]);
~~~

>
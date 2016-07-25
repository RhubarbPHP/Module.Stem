Intersections
=============

Any developer familiar with SQL will know just how often the
need arises to use a join query to link several tables together in order
to filter on columns in those tables or to aggregate their values. Stem
provides a similar concept through intersections. In fact if using a
repository that supports intersections (e.g. MySql) it will ultimately
resolve into join queries being used.

It's important to point out however that intersections are not
exactly the same as performing a join. Intersections allow you to
achieve the following things:

1. remove rows in the first collection where a matching row can't be
   found in a second collection
2. using the first collection as a group index, aggregate columns in
   the matching rows of the second collection, and pull them up into
   the selection of the first collection for selection or filtering
3. selection of data needed to populate the models of a relationship
   of the first model.

You can intersect a collection with any number of other collections and
those collections can themselves be intersected with others. There is
no limit to the depth of nested intersections except perhaps any
imposed by your repository.

Intersections can handle 99% of selection scenarios you'll need in a
standard enterprise application and their real benefit is that they
allow those complex selections to be unit tested without requiring 
a connected database.

## Basic syntax

To intersect we call the `intersectWith()` function on the collection.
The arguments are in turn:

1. The collection to intersect with
2. The column in the outer collection containing a value to find in the 
   inner collection
3. The column in the inner collecting containing the value to find from
   the outer collection
4. Optionally an array of column names to 'pull up' from the inner
   collection to the outer collection. This makes these columns available
   for selection or filtering
5. Optionally a bool indicating if the intersection should be auto-hydrated.

Imagine you had a collection of contacts and a collection of companies.
There is a one to many relationship between the company and the contacts.
Imagine you wanted to get a collection of only contacts whose company
location is in a particular city. Here's the code:

``` php
$contacts = Contact::all()
              ->intersectWith(
                  Company::find(
                    new Equals("City", "Craigavon")
                    ),
                  "CompanyID",
                  "CompanyID");
              
```

Let's break this down a little:

**Contact::all()** retrieves all contacts  
**intersectWith** is the function we call to filter out rows that don't
appear in a target collection  
**Company::find(new Equals("City", "Craigavon"))** returns a collection of
companies in the Craigavon city.  
**"CompanyID"** is the name of the joining field in both the parent and
target collections.

Sometimes it can be easier to setup the collections first as variables
and then do the intersection:

``` php
// Find qualifying companies first
$companies = Company::find(new Equals("City", "Craigavon"));

// Now intersect those with contacts to find the contacts of those 
// companies.
$contacts = Contact::all();
$contacts->intersectWith(
                  $companies,                  
                  "CompanyID",
                  "CompanyID");
```

> It can be helpful to think of the final two parameters as the 'ON'
> clause of a standard SQL join. Just like an ON clause they can be
> any field in either model, however unlike an ON clause you can only
> have one constraint.

While it can be quite hard at first to visualise what's happening here
intersections can be easier to create than complex SQL join queries.
A join query generally needs thought about all in one go however with
an intersection you can build in stages. Define the simplest 'inner'
collection first then work outwards intersecting with each outer
collection. As you go you can count and verify the number of rows.

Once you realise how easy it is to nest intersections you realise it
can let you achieve quite powerful results that are difficult to hand
craft in SQL. For example let's modify our example above a little. Now
we want to find contacts who belong to company where the number of
contacts that company has is greater than 10. Not a trivial query in
SQL but here's the Stem code:

``` php
// Select companies with more than 10 people
$companies = Company::all()                 // Select all companies
                ->intersectWith(            // but intersect with
                    Contact:all()           // all contacts
                        ->addAggregateColumn(new Count("Contacts")),  // count the number of contacts per company
                    "CompanyID",            // join using the CompanyID field
                    "CompanyID",
                    ["CountOfContacts"]     // pull up the count into the company collection
                    )
                ->filter(new GreaterThan("CountOfContacts", 10));  // filter on the count of contacts (previously pulled up)

// As before now we have a list of companies with more than 10 people
// in them we can intersect with contacts to find who they are:
$contacts = Contact::all();
$contacts->intersectWith(
                  $companies,                  
                  "CompanyID",
                  "CompanyID");
```

This time we've involved an aggregate on the inner most Contact 
collection and then pulled that aggregate value up into the Company
collection allowing us to filter it for those with a value > 10.
Finally we intersect that collection with the list of contacts to find
the actual contacts.

### Shorthand syntax - dot notation.

Where [relationships](relationships) have been defined filtering,
sorting and aggregating support a dot notation syntax which can 
setup the intersection for you and make the expression more legible.
Here's an alternative version of the first example:

``` php
// Use dot notation to infer the correct intersection.
$contacts = Contact::find(new Equals("Company.City", "Craigavon"));
```

That's a lot easier to understand for most people.

Here's the second example redrafted:

``` php
// Select companies with more than 10 people
$companies = Company::all()
                ->addAggregateColumn(new Count("Contacts.ContactID" => "CountOfContacts")),
                ->filter(new GreaterThan("CountOfContacts", 10));

// As before now we have a list of companies with more than 10 people
// in them we can intersect with contacts to find who they are:
$contacts = Contact::all();
$contacts->intersectWith(
                  $companies,                  
                  "CompanyID",
                  "CompanyID");
```

The dot notation here is in the aggregate column. By using it we 
automatically get an intersection with a contact collection.

> Note that we specify an alias manually for the aggregate otherwise its
> automatic alias would have been the cumbersome CountOfContactsContactID!

## Grouping

If you aggregate on a collection involved inside an intersection, that
collection will automatically be grouped using the column name of the
constraint on the intersection.

Grouping is also be applied to the outermost collection by its
unique identifier column name ensuring the collection only outputs
one model per ID.

## Pull ups

In a SQL join query you can select from any of the tables involved
however you need to be careful to alias any column names that might
be duplicated. Both to avoid these types of conflicts and because
you can intersect between collections from different repositories
you need to specify the names of columns you need to 'pull up' into
the parent collection.

Once pulled up you can treat them like real columns filtering, sorting,
aggregating and grouping on them just like any other. This again can
make complex groups and aggregates more straightforward. For example
it's possible to group an inner collection to calculate an aggregate,
pull it up to the outer collection and then group the outer collection
using that aggregate.

Pull ups are listed as the forth argument to `intersectWith`. You can
provide an array of just column names, or if you want to alias the
columns you can provide an associative array of original to alias names.

## Auto hydration

In it's normal mode an intersection serves to filter a collection for
matching records; the actual data (bar pull ups) in the intersection
is not selected or used in any way. However if when writing the
intersection you know that you will very likely to use that data
in the following code you should try to auto hydrate the intersections.

For example using our example above, let's say you wanted to loop
through the contacts matching our selection and do something with their
related company models:

``` php
// Find qualifying companies first
$companies = Company::find(new Equals("City", "Craigavon"));

// Now intersect those with contacts to find the contacts of those 
// companies.
$contacts = Contact::all();
$contacts->intersectWith(
                  $companies,                  
                  "CompanyID",
                  "CompanyID");

foreach($contacts as $contact){
    // By accessing the relationship to `Company` we will cause another
    // hit on our repository usually at high cost of network overhead.
    print $contact->Company->CompanyName;
}
```

To avoid hitting th repository again for each record in the contacts
collection we can modify our collection by passing true as the next
argument to `intersectWith`:

``` php
// Find qualifying companies first
$companies = Company::find(new Equals("City", "Craigavon"));

// Now intersect those with contacts to find the contacts of those 
// companies.
$contacts = Contact::all();
$contacts->intersectWith(
                  $companies,                  
                  "CompanyID",
                  "CompanyID",
                  true);

foreach($contacts as $contact){
    // Because of the auto-hydration accessing the relationship to
    // `Company` will no longer cause another hit on our repository.
    print $contact->Company->CompanyName;
}
```

When using dot notation auto-hydration is automatically enabled for
intersections it creates.


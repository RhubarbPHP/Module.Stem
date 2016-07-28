Grouping
========

To combine multiple filters we use the GroupFilter, or more specifically the AndGroup and OrGroup filters.
Group filters are constructed with any number of filter objects and they will be AND'ed or OR'ed together.

## Creating a group

Simple construct an AndGroup or an OrGroup object and pass any number of filter objects as separate parameters:

``` php
// Filter to find John Smith
new AndGroup(
    new Equals("Forename", "John",
    new Equals("Surname", "Smith")
    );

// Filter to find people called John OR with a surname of Smith
new OrGroup(
    new Equals("Forename", "John",
    new Equals("Surname", "Smith")
    );
```

For complex expressions, simply nest groups as arguments to parent groups:

``` php
// Filter to find all John Smiths who either have a salary greater than 30,000 OR have a job title of "Developer"
new AndGroup(
    new Equals("Forename", "John",
    new Equals("Surname", "Smith",
    new OrGroup(
        new GreaterThan("Salary", 30000),
        new Equals("JobTitle", "Developer"
        )
    );
```

### Adding additional filters

With an existing group object you can call `addFilters` to add additional filters to the group.

``` php
$group->addFilters(new Equals("HairColour", "Brown"));
```
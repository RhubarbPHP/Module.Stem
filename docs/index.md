Modelling with Stem
===================

Data modelling is the pattern of encapsulating data into objects and describing the relationships and
interactions between them. Modelling lets us improve the clarity of our code by organising it into
classes named after the real world concepts they relate to.

Model classes have a 'Schema' which describes the 'Columns' of data it contains. Your application has a
'SolutionSchema' which lists the models you're using, how they're related and controls their interactions.

Models are stored in a 'Repository', for example a MySQL repository.

Models can be created singly or accessed using a 'Collection' which can return all the models for a particular type
or a subset by using one or more 'Filter' objects.

You can intersect one collection with another and perform aggregates on groups such as Sum, Count, CountDistinct etc.

## Modelling Topics

[Model & Schema](models-and-schemas)
:	Represents single 'records' of data and how they interrelate.

[Collection](collections)
:	An iterable collection of models of the same type

[Repository](repositories)
:	Connects models with a data store

[Filter](filters/index)
:	Provides a way to search lists for matching models
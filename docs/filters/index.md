# Filters #

Filters reduce Collections to a subset of matching Models. Filters are
expressed using Filter objects and in the absence of repository support
work by iterating over the items in a list and building a list of
records to remove from the collection. If it supports it, the repository
can use the filter directly to customise its query to avoid
expensive iteration.

Most filters operate on a single model record and apply a simple
expression, like equals, more than, less than, contains etc. Some
filters however are more complex. The `Group` filter for example
contains a collection of other filters ANDed or ORed together. The
`Not` filter inverts the selection of any other filter given to it.


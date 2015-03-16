# Filters #

Filters reduce ModelLists to only matching Models. They do this by iterating over the items in a list and building a range of unique identifiers to remove from the list. If it supports it the repository connected to the data object in the list can use the filter directly to customise it's query to avoid the expensive iteration.

A filter can use any algorithm it needs to in order to make it's decision about a particular model. That means that while a filter normally checks a single column against a single value they can do much more. The `Group` filter for example contains a collection of other filters ANDed or ORed together. The `Not` filter inverts the selection of any other filter given to it.
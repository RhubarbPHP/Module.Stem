<?php

namespace Rhubarb\Stem\Filters;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Stem\Collections\Collection;


/**
 * Filters by finding models that exist in another collection using their unique identifier
 */
class InCollection extends ColumnFilter
{
    /**
     * @var Collection The collection we're searching against
     */
    protected $collection;

    /**
     * @var string The name of the column in the collection containing the unique identifier of the model we're looking for
     */
    protected $columnInCollection;

    public function __construct($columnName, Collection $collection, $columnInCollection = "" )
    {
        parent::__construct($columnName);

        $this->columnInCollection = $columnInCollection;
        $this->collection = $collection;
    }

    public function getSettingsArray()
    {
        throw new ImplementationException( "This filter can't be expressed as an array. You must extend the class" .
        "to provide some scalar basis for the collection property that be populated in an array of settings");
    }

    /**
     * Implement to return an array of unique identifiers to filter from the list.
     *
     * @param Collection $list The data list to filter.
     * @return array
     */
    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $column = $this->columnInCollection;
        $potentialMatches = [];
        $idsToFilter = [];

        foreach( $this->collection as $model ) {

            if ( $column == "" ) {
                $column = $model->UniqueIdentifierColumnName;
            }

            $potentialMatches[] = $model[ $column ];
        }

        foreach( $list as $model ) {
            $idsToFilter[] = 
        }
    }
}
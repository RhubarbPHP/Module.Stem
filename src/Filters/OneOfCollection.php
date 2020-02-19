<?php

namespace Rhubarb\Stem\Filters;

use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;

class OneOfCollection extends ColumnFilter
{
    /**
     * @var RepositoryCollection
     */
    protected $collection;
    /**
     * @var string|null
     */
    protected $collectionColumnName;

    /**
     * OneOfCollection constructor.
     *
     * @param string      $columnName
     * @param Collection  $collection
     * @param string|null $collectionColumnName
     */
    public function __construct($columnName, Collection $collection, $collectionColumnName = null)
    {
        parent::__construct($columnName);
        $this->collection = $collection;
        $this->collectionColumnName = $collectionColumnName ? $collectionColumnName : $collection->getModelSchema()->uniqueIdentifierColumnName;
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Model $model)
    {
        $column = $this->collectionColumnName;
        foreach ($this->collection as $compare) {
            if ($model[$this->columnName] == $compare->$column) {
                return false;
            }
        }
        return true;
    }
}

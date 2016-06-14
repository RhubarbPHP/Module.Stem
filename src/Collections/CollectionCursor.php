<?php

namespace Rhubarb\Stem\Collections;

abstract class CollectionCursor implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * True if the cursor has been filtered.
     *
     * @var bool
     */
    public $filtered = false;

    /**
     * The augmentation data.
     *
     * @see setAugmentationData()
     * @var array
     */
    protected $augmentationData = [];

    public abstract function filterModelsByIdentifier($uniqueIdentifiers);

    /**
     * Sets the augmentation data for the collection.
     *
     * Augmentation data is data in an array, keyed by unique identifier which is added
     * to the model when it is being generated during access.
     *
     * This avoids the need to cache whole model objects during iteration and filtering.
     *
     * @param string[] $data
     */
    public final function setAugmentationData($data)
    {
        foreach($data as $id => $rowData){
            if (!isset($this->augmentationData[$id])){
                $this->augmentationData[$id] = $rowData;
            } else {
                $this->augmentationData[$id] = array_merge($this->augmentationData[$id], $rowData);
            }
        }
    }

    public final function getAugmentationData()
    {
        return $this->augmentationData;
    }
}
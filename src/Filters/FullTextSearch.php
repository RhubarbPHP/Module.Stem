<?php

namespace Rhubarb\Stem\Filters;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\String\StringTools;
use Rhubarb\Stem\Collections\Collection;

class FullTextSearch extends Filter
{
    const MODE_NATURAL_LANGUAGE = "NATURAL LANGUAGE";
    const MODE_BOOLEAN = "BOOLEAN";
    const MODE_QUERY_EXPANSION = "QUERY EXPANSION";
    const MODES = [
        self::MODE_NATURAL_LANGUAGE,
        self::MODE_BOOLEAN,
        self::MODE_QUERY_EXPANSION
    ];

    /** @var array $indexColumns */
    protected $indexColumns;

    /** @var string $searchPhrase */
    protected $searchPhrase;

    /** @var string $mode */
    protected $mode;

    /**
     * Builds a filter that supports full text searching for MySql database tables.
     *
     * @param array $indexColumns The columns that comprise the index. Must be in the same order as the index itself
     * @param string $searchPhrase The search phrase to match on
     * @param string $mode The mode for the search filter. Can be one of "NATURAL LANGUAGE", "BOOLEAN" or "QUERY EXPANSION".
     *
     * @throws ImplementationException
     */
    public function __construct(array $indexColumns, $searchPhrase, $mode = "NATURAL LANGUAGE")
    {
        if (strlen(StringTools::implodeIgnoringBlanks("", $indexColumns)) === 0) {
            throw new ImplementationException("Index columns passed an empty array");
        }

        $this->indexColumns = $indexColumns;
        $this->searchPhrase = $searchPhrase;
        $this->mode = $mode;
    }

    /**
     * @inheritDoc
     */
    public function doGetUniqueIdentifiersToFilter(Collection $list)
    {
        $ids = [];

        //We need to make sure our search value does not contain any diacritics
        $searchValueCleaned = preg_replace('/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i','$1', htmlentities($this->searchPhrase));
        foreach ($list as $item) {
            $columnsToSearch = $this->indexColumns;

            foreach ($columnsToSearch as $column) {
                if (preg_match("/$searchValueCleaned/i", $item[$column])) {
                    $ids[] = $item->UniqueIdentifier;
                    break;
                }
            }
        }

        return $ids;
    }

    /**
     * @inheritDoc
     */
    public function getSettingsArray()
    {
        $settings = parent::getSettingsArray();
        $settings["indexColumns"] = $this->indexColumns;
        $settings["searchPhrase"] = $this->searchPhrase;
        $settings["mode"] = $this->mode;

        return $settings;
    }

    /**
     * Builds a new instance of the FullTextSearch from the settings array
     *
     * @param $settings
     *
     * @return FullTextSearch
     *
     * @throws ImplementationException
     */
    public static function fromSettingsArray($settings)
    {
        return new self($settings["indexColumns"], $settings["searchPhrase"], $settings["mode"]);
    }
}

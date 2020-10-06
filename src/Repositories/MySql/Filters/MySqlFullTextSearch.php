<?php

namespace Rhubarb\Stem\Repositories\MySql\Filters;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\String\StringTools;
use Rhubarb\Stem\Filters\Filter;
use Rhubarb\Stem\Filters\FullTextSearch;
use Rhubarb\Stem\Repositories\Repository;

class MySqlFullTextSearch extends FullTextSearch
{
    use MySqlFilterTrait {
        canFilter as canFilterTrait;
    }

    /**
     * @inheritDoc
     */
    protected static function doFilterWithRepository(Repository $repository, Filter $originalFilter, &$params, &$propertiesToAutoHydrate)
    {
        $settings = $originalFilter->getSettingsArray();
        if (!isset($settings["indexColumns"]) || !isset($settings["searchPhrase"]) || !isset($settings["mode"])) {
            throw new ImplementationException("Filter passed to doFilterWithRepository was \"" . get_class($originalFilter) . "\" expected " . \Unislim\WebApp\Shared\Filters\MySqlFullTextSearch::class);
        }

        $implodedColumns = StringTools::implodeIgnoringBlanks(",", $settings["indexColumns"]);
        if (self::canFilter($repository, $implodedColumns, $propertiesToAutoHydrate)) {
            $searchPhrase = $settings["searchPhrase"];
            $paramName = uniqid() . str_replace(".", "", $implodedColumns);
            $params[$paramName] = $searchPhrase;

            $originalFilter->filteredByRepository = true;

            $words = preg_split("/\s+/", $searchPhrase);            
            $newWords = [];
            foreach($words as $word){
                $newWords[] = $word."*";
            }

            $searchPhrase = implode(" ", $newWords);

            return "MATCH ({$implodedColumns}) AGAINST ('{$searchPhrase}' IN {$settings["mode"]} MODE)";
        }

        parent::doFilterWithRepository($repository, $originalFilter, $params, $propertiesToAutoHydrate);
    }

    /**
     * @inheritDoc
     */
    protected static function canFilter(Repository $repository, $columnName, &$propertiesToAutoHydrate)
    {
        $schema = $repository->getRepositorySchema();
        $columns = $schema->getColumns();

        //We need to explode the column name as we will have passed an imploded list of columns that
        //comprise the index
        $providedColumns = preg_split('@/@', $columnName, NULL, PREG_SPLIT_NO_EMPTY);
        foreach ($providedColumns as $column) {
            if (!isset($column, $columns)) {
                if (!self::canFilterTrait($repository, $column, $propertiesToAutoHydrate)) {
                    return false;
                }
            }
        }

        return true;
    }
}

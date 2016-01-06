<?php

namespace Rhubarb\Stem\Custard;

use phpDocumentor\Reflection\DocBlock;
use Rhubarb\Crown\Exceptions\RhubarbException;
use Rhubarb\Crown\String\StringTools;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Relationships\ManyToMany;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\Relationships\Relationship;
use Rhubarb\Stem\Schema\SolutionSchema;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentModelsCommand extends CustardCommand
{
    protected function configure()
    {
        $this->setName('stem:document-models')
            ->setDescription('Generate phpDoc comments for Rhubarb Stem models, describing their fields and relationships')
            ->addArgument('schema', InputArgument::REQUIRED, 'The name of the schema to scan models in');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $schemaName = $input->getArgument('schema');

        SolutionSchema::getAllSchemas();

        try {
            $schema = SolutionSchema::getSchema($schemaName);
        } catch (SchemaNotFoundException $ex) {
            $output->writeln("<error>Couldn't find schema named '$schemaName'</error>");
            return;
        } catch (SchemaRegistrationException $ex) {
            $output->writeln("<error>Schema registered as '$schemaName' is not a SolutionSchema</error>");
            return;
        }

        $changedModels = 0;

        foreach ($schema->getAllModels() as $modelName => $modelClass) {
            $this->writeVerbose("Processing $modelName... ");
            /**
 * @var Model $model
*/
            $model = new $modelClass();

            $reflectionClass = new \ReflectionClass($model);
            $doc = new DocBlock($reflectionClass);

            /**
 * @var DocBlock\Tag\PropertyTag[] $properties
*/
            $properties = $doc->getTagsByName('property');
            /**
 * @var DocBlock\Tag\PropertyTag[] $namedProperties
*/
            $namedProperties = [];
            foreach ($properties as $property) {
                $namedProperties[$property->getVariableName()] = $property;
            }

            $relationships = $schema->getAllRelationshipsForModel($model->getModelName());

            try {
                $changed = self::addPropertiesForColumns($model, $namedProperties, $doc);
                $changed = self::addPropertiesForRelationships($relationships, $namedProperties, $doc) || $changed;
            } catch (RhubarbException $ex) {
                $output->writeln($ex->getMessage());
                continue;
            }

            if ($changed) {
                $this->writeVerbose("changes detected, writing to " . $reflectionClass->getFileName(), true);
                $changedModels++;

                self::writePhpDoc($doc, $reflectionClass);
            } else {
                $this->writeVerbose("no changes detected, moving on", true);
            }
        }

        $this->writeNormal("$changedModels model phpDoc comments updated, finished", true);
    }

    /**
     * @param Column $column
     * @return string
     */
    private static function getPropertyContentForColumn(Column $column)
    {
        return $column->getPhpType() . ' $' . $column->columnName;
    }

    /**
     * @param Relationship $relationship
     * @return string
     * @throws RhubarbException If the relationship type is unsupported
     */
    private static function getPropertyContentForRelationship(Relationship $relationship)
    {
        $collectionType = false;
        if ($relationship instanceof OneToOne) {
            $modelName = $relationship->getTargetModelName();
        } else if ($relationship instanceof OneToMany) {
            $modelName = $relationship->getTargetModelName();
            $collectionType = true;
        } else if ($relationship instanceof ManyToMany) {
            $modelName = $relationship->getRightModelName();
            $collectionType = true;
        } else {
            throw new RhubarbException('Unsupported relationship type: ' . get_class($relationship));
        }

        $type = SolutionSchema::getModelClass($modelName);

        if ($type == null) {
            throw new RhubarbException('Unregistered model used in relationship: ' . $modelName);
        }

        if ($collectionType) {
            $type .= '[]|\\' . Collection::class;
        }

        return $type . ' $' . $relationship->getNavigationPropertyName();
    }

    /**
     * @param Model                      $model
     * @param DocBlock\Tag\PropertyTag[] $existingProperties
     * @param DocBlock                   $docBlock
     * @return bool True if any properties have been added/changed
     */
    private static function addPropertiesForColumns($model, $existingProperties, $docBlock)
    {
        $changed = false;
        foreach ($model->getSchema()->getColumns() as $field) {
            if (isset($existingProperties['$' . $field->columnName])) {
                // Update existing phpDoc field
                $tag = $existingProperties['$' . $field->columnName];
                $newContent = self::getPropertyContentForColumn($field);
                if ($newContent != $tag->getContent()) {
                    $tag->setContent($newContent);
                    $changed = true;
                }
            } else {
                // Add new field
                $tag = new DocBlock\Tag\PropertyTag("property", self::getPropertyContentForColumn($field));
                $docBlock->appendTag($tag);
                $changed = true;
            }
        }
        return $changed;
    }

    /**
     * @param Relationship[]             $relationships
     * @param DocBlock\Tag\PropertyTag[] $existingProperties
     * @param DocBlock                   $docBlock
     * @return bool True if any properties have been added/changed
     */
    private static function addPropertiesForRelationships($relationships, $existingProperties, $docBlock)
    {
        $changed = false;
        foreach ($relationships as $relationship) {
            $propertyName = $relationship->getNavigationPropertyName();
            if (isset($existingProperties['$' . $propertyName])) {
                // Update existing phpDoc field
                $tag = $existingProperties['$' . $propertyName];
                $newContent = self::getPropertyContentForRelationship($relationship);
                if ($newContent != $tag->getContent()) {
                    $tag->setContent($newContent);
                    $changed = true;
                }
            } else {
                // Add new field
                $tag = new DocBlock\Tag\PropertyTag("property", self::getPropertyContentForRelationship($relationship));
                $docBlock->appendTag($tag);
                $changed = true;
            }
        }
        return $changed;
    }

    private static function writePhpDoc(DocBlock $docBlock, \ReflectionClass $reflectionClass)
    {
        $fileContents = file_get_contents($reflectionClass->getFileName());
        $existingDoc = $reflectionClass->getDocComment();

        $serializer = new DocBlock\Serializer();
        $newDoc = $serializer->getDocComment($docBlock);

        $newDoc = preg_replace('/\s+$/m', '', $newDoc);

        if ($existingDoc) {
            $fileContents = StringTools::replaceFirst($existingDoc, $newDoc, $fileContents);
        } else {
            $fileContents = preg_replace('/^(class\s+' . $reflectionClass->getShortName() . ')/m', str_replace('$', '\$', $newDoc) . "\n\$1", $fileContents, 1);
        }

        file_put_contents($reflectionClass->getFileName(), $fileContents);
    }
}

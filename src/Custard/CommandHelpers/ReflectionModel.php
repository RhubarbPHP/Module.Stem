<?php

namespace Rhubarb\Stem\Custard\CommandHelpers;

use phpDocumentor\Reflection\DocBlock;
use Rhubarb\Crown\Exceptions\RhubarbException;
use Rhubarb\Crown\String\StringTools;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Relationships\ManyToMany;
use Rhubarb\Stem\Schema\Relationships\OneToMany;
use Rhubarb\Stem\Schema\Relationships\OneToOne;
use Rhubarb\Stem\Schema\Relationships\Relationship;
use Rhubarb\Stem\Schema\SolutionSchema;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) If you can't stand the heat, get outta my kitchen
 */
class ReflectionModel extends \ReflectionClass
{
    /** @var Model */
    protected $model;

    /** @var DocBlock */
    protected $classDocBlock;

    /** @var DocBlock\Tag\PropertyTag[] */
    protected $fields = [];

    /** @var \Rhubarb\Stem\Schema\Relationships\Relationship[] */
    protected $relationships;

    /** @var GetterOrSetterMethod[] */
    protected $gettersAndSetters = [];

    /** @var string[] */
    protected $availableClassAliases;

    /** @var string[] Names of properties observed as current during updateDocBlock() */
    protected $touchedProperties;

    public function __construct(Model $model)
    {
        parent::__construct($model);

        $this->model = $model;

        $this->classDocBlock = new DocBlock($this);

        $this->queryFields();

        $this->queryRelationships();

        $this->queryGettersAndSetters();

        $this->queryAvailableClassAliases();
    }

    protected function queryFields()
    {
        $properties = $this->classDocBlock->getTags();
        foreach ($properties as $property) {
            if ($property instanceof DocBlock\Tag\PropertyTag) {
                $this->fields[$property->getVariableName()] = $property;
            }
        }
    }

    protected function queryRelationships()
    {
        $this->relationships = SolutionSchema::getAllRelationshipsForModel($this->model->getModelName());
    }

    protected function queryGettersAndSetters()
    {
        $methods = $this->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->isStatic() || $method->getDeclaringClass()->getFileName() != $this->getFileName()) {
                continue;
            }

            $method = GetterOrSetterMethod::fromReflectionMethod($method, $this->gettersAndSetters);
            if ($method) {
                $this->gettersAndSetters[$method->getPropertyName()] = $method;
            }
        }
    }

    protected function queryAvailableClassAliases()
    {
        $classCode = file_get_contents($this->getFileName());
        $parser = new TokenParser($classCode);
        $uses = $parser->parseUseStatements($this->getName());

        $namespaces = [];
        // Ensure all imported namespaces have a leading slash
        foreach ($uses as $use) {
            $className = StringTools::getCharsAfterMatch($use, '\\', false, null, true, false, $use);
            $namespaces[$className] = (strpos($use, '\\') === 0 ? '' : '\\') . $use;
        }

        $this->availableClassAliases = $namespaces;
    }

    /**
     * @param bool $updateExisting Set to update the definitions of existing properties
     * @param bool $removeOld Set to remove the definitions of properties which are no longer matched
     * @param bool $rewriteDescriptions Set to overwrite existing property descriptions with generated ones
     * @return bool True if there were changes detected
     */
    public function updateDocBlock($updateExisting, $removeOld, $rewriteDescriptions)
    {
        $this->touchedProperties = [];

        $changed = $this->addPropertiesForColumns($updateExisting, $rewriteDescriptions);
        $changed |= $this->addPropertiesForRelationships($updateExisting, $rewriteDescriptions);
        $changed |= $this->addPropertiesForGettersAndSetters($updateExisting, $rewriteDescriptions);

        if ($removeOld) {
            $propertiesToRemove = array_diff(array_keys($this->fields), $this->touchedProperties);
            if (count($propertiesToRemove)) {
                DocBlockHelper::removePropertyTags($this->classDocBlock, $propertiesToRemove);
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param bool $updateExisting
     * @param bool $rewriteDescriptions
     * @return bool True if any properties have been added/changed
     */
    protected function addPropertiesForColumns($updateExisting, $rewriteDescriptions)
    {
        $changed = false;

        foreach ($this->model->getSchema()->getColumns() as $field) {
            $content = $this->getPropertyContentForColumn($field);

            $changed |= $this->setProperty($updateExisting, 'property', $field->columnName, $content, $rewriteDescriptions, 'Repository field');
        }

        return $changed;
    }

    /**
     * @param $updateExisting
     * @param bool $rewriteDescriptions
     * @return bool True if any properties have been added/changed
     */
    private function addPropertiesForRelationships($updateExisting, $rewriteDescriptions)
    {
        $changed = false;

        foreach ($this->relationships as $relationship) {
            $propertyName = $relationship->getNavigationPropertyName();

            $content = $this->getPropertyContentForRelationship($relationship);

            $changed |= $this->setProperty($updateExisting, 'property-read', $propertyName, $content, $rewriteDescriptions, 'Relationship');
        }

        return $changed;
    }

    /**
     * @param $updateExisting
     * @param bool $rewriteDescriptions
     * @return bool True if any properties have been added/changed
     */
    private function addPropertiesForGettersAndSetters($updateExisting, $rewriteDescriptions)
    {
        $changed = false;

        foreach ($this->gettersAndSetters as $propertyName => $method) {
            $content = $this->getPropertyContentForGetterOrSetter($method->getReflectionMethod(), $method->isReadable(), $description);

            $tagName = $method->getTagName();

            if ($method->isReadable()) {
                $description = trim($description . " {@link get$propertyName()}");
            }
            if ($method->isWritable()) {
                $description = trim($description . " {@link set$propertyName()}");
            }

            $changed |= $this->setProperty($updateExisting, $tagName, $propertyName, $content, $rewriteDescriptions, $description);
        }

        return $changed;
    }

    /**
     * Add a property to the list of touched properties, unless it is already present.
     *
     * @param $propertyName
     * @return bool False if the property was already touched
     */
    protected function touchProperty($propertyName)
    {
        if (in_array('$' . $propertyName, $this->touchedProperties)) {
            return false;
        }
        $this->touchedProperties[] = '$' . $propertyName;
        return true;
    }

    /**
     * @param Column $column
     * @return string
     */
    protected function getPropertyContentForColumn(Column $column)
    {
        $types = explode('|', $column->getPhpType());

        $types = $this->shortenNamespaces($types);

        return implode('|', $types) . ' $' . $column->columnName;
    }

    /**
     * @param Relationship $relationship
     * @return string
     * @throws RhubarbException If the relationship type is unsupported
     */
    protected function getPropertyContentForRelationship(Relationship $relationship)
    {
        $collectionType = false;
        if ($relationship instanceof OneToOne) {
            $modelName = $relationship->getTargetModelName();
        } elseif ($relationship instanceof OneToMany) {
            $modelName = $relationship->getTargetModelName();
            $collectionType = true;
        } elseif ($relationship instanceof ManyToMany) {
            $modelName = $relationship->getRightModelName();
            $collectionType = true;
        } else {
            throw new RhubarbException('Unsupported relationship type: ' . get_class($relationship));
        }

        $type = SolutionSchema::getSchemaClass($modelName);

        if ($type == null) {
            throw new RhubarbException('Unregistered model used in relationship: ' . $modelName);
        }

        if ($collectionType) {
            $types = [$type . '[]', '\\' . RepositoryCollection::class];
        } else {
            $types = [$type];
        }

        $types = $this->shortenNamespaces($types);

        return implode('|', $types) . ' $' . $relationship->getNavigationPropertyName();
    }

    protected function getPropertyContentForGetterOrSetter(\ReflectionMethod $method, $getter, &$description)
    {
        $currentNamespace = $method->getDeclaringClass()->getNamespaceName();
        $context = new DocBlock\Context($currentNamespace, $this->availableClassAliases);
        $comment = new DocBlock($method->getDocComment(), $context);

        $tagName = $getter ? 'return' : 'param';

        if ($comment->hasTag($tagName)) {
            /** @var DocBlock\Tag\ReturnTag $returnTag */
            $returnTag = $comment->getTagsByName($tagName)[0];

            $type = $this->getTypeForTag($returnTag);
        } else {
            $type = 'mixed';
        }

        $description = $comment->getShortDescription();

        return $type . ' $' . substr($method->getName(), 3);
    }

    protected function getTypeForTag(DocBlock\Tag\ReturnTag $tag)
    {
        $types = $this->shortenNamespaces($tag->getTypes());

        return implode('|', $types);
    }

    /**
     * @param string[] $types
     * @return string[]
     */
    protected function shortenNamespaces($types)
    {
        $currentNamespace = $this->getNamespaceName();
        if (strpos($currentNamespace, '\\') !== 0) {
            $currentNamespace = '\\' . $currentNamespace;
        }

        foreach ($types as &$type) {
            if (strpos($type, '\\') === false) {
                // If there is no namespace separator in the type, it doesn't need to be shorted
                continue;
            }

            $isArray = false;
            if (StringTools::endsWith($type, '[]')) {
                // If this is an array type, we need to temporarily remove the array markers for namespace comparison
                $isArray = true;
                $type = StringTools::removeCharsFromEnd($type, 2);
            }

            if (strpos($type, $currentNamespace) === 0) {
                // Attempt to match the type to the current class's namespace
                $type = substr($type, strlen($currentNamespace) + 1);
            } else {
                // Attempt to match the type to the imported namespaces
                $type = $this->shortenNamespaceWithImportedNamespaces($type);
            }

            if ($isArray) {
                $type .= '[]';
            }
        }

        return $types;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function shortenNamespaceWithImportedNamespaces($type)
    {
        foreach ($this->availableClassAliases as $alias => $namespace) {
            if ($type == $namespace) {
                // If the type is an exact match for the namespace, use the shortened alias
                return $alias;
            }

            $namespace .= '\\';
            if (strpos($type, $namespace) === 0) {
                // If the type starts with the imported namespace, remove it to shorten the type
                return $alias . substr($type, strlen($namespace));
            }
        }
        return $type;
    }

    /**
     * @param bool $updateExisting Whether the content should be updated if the property definition already exists
     * @param string $tagName One of 'property', 'property-read', or 'property-write'
     * @param string $variableName Name of the property
     * @param string $content New content for the property definition
     * @param bool $rewriteDescriptions Whether the description should be updated if the property definition already has one
     * @param string $description If not set, any existing description will be retained
     * @return bool True if a change was made
     */
    protected function setProperty($updateExisting, $tagName, $variableName, $content, $rewriteDescriptions, $description = null)
    {
        if (!$this->touchProperty($variableName)) {
            return false;
        }

        $changed = false;

        if (isset($this->fields['$' . $variableName])) {
            if ($updateExisting) {
                // Update existing phpDoc field
                $tag = $this->fields['$' . $variableName];

                if ($tagName != $tag->getName()) {
                    $tag->setName($tagName);
                    $changed = true;
                }

                $tagDescription = $tag->getDescription();
                if (!$rewriteDescriptions && $tagDescription) {
                    $description = $tagDescription;
                }
                $content = trim($content . ' ' . $description);

                if ($content != $tag->getContent()) {
                    $tag->setContent($content);
                    $changed = true;
                }
            }
        } else {
            // Add new field
            $content = trim($content . ' ' . $description);
            $tag = new DocBlock\Tag\PropertyTag($tagName, $content);
            $this->classDocBlock->appendTag($tag);
            $changed = true;
        }

        return $changed;
    }

    /**
     * Writes the updated DocBlock to the model's file
     */
    public function writePhpDoc()
    {
        $fileContents = file_get_contents($this->getFileName());
        $existingDoc = $this->getDocComment();

        $serializer = new DocBlock\Serializer();
        $newDoc = $serializer->getDocComment($this->classDocBlock);

        // PhpDocumentor inserts a space between @SuppressWarnings and its following bracket,
        // which prevents PHP Mess Detector from picking up on the comment
        $newDoc = str_replace('@SuppressWarnings (', '@SuppressWarnings(', $newDoc);

        // Remove whitespace from end of all lines in doc block
        $newDoc = preg_replace('/\s+$/m', '', $newDoc);

        if ($existingDoc) {
            $fileContents = StringTools::replaceFirst($existingDoc, $newDoc, $fileContents);
        } else {
            $fileContents = preg_replace('/^(class\s+' . $this->getShortName() . ')/m', str_replace('$', '\$', $newDoc) . "\n\$1", $fileContents, 1);
        }

        file_put_contents($this->getFileName(), $fileContents);
    }
}

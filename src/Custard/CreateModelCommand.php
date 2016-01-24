<?php

namespace Rhubarb\Stem\Custard;

use phpDocumentor\Reflection\DocBlock;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Stem\Custard\CommandHelpers\SchemaCommandTrait;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\Columns\DateColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\DecimalColumn;
use Rhubarb\Stem\Schema\Columns\FloatColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\Columns\LongStringColumn;
use Rhubarb\Stem\Schema\Columns\MoneyColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\Columns\TimeColumn;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateModelCommand extends CustardCommand
{
    use SchemaCommandTrait;

    protected static $columnTypes = [
        "Boolean" => BooleanColumn::class,
        "Integer" => IntegerColumn::class,
        "ForeignKey" => ForeignKeyColumn::class,
        "Float" => FloatColumn::class,
        "Decimal" => DecimalColumn::class,
        "Money" => MoneyColumn::class,
        "Date" => DateColumn::class,
        "DateTime" => DateTimeColumn::class,
        "Time" => TimeColumn::class
    ];
    protected static $columnTypesWithMaxLength = [
        "String" => StringColumn::class,
        "LongString" => LongStringColumn::class
    ];

    protected function configure()
    {
        $this->setName('stem:create-model')
            ->setDescription('Create a model class and add it to the schema');
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $schema = $this->getSchema('What schema do you want to add this model to?');

        $reflector = new \ReflectionClass($schema);

        $namespaceBase = $reflector->getNamespaceName() . '\\';

        $subNamespace = $this->askQuestion("<question>What namespace should this model be in?</question> $namespaceBase", null, false);
        $namespace = trim($namespaceBase . $subNamespace, '\\');

        $modelName = $this->askQuestion('What name should this model have?');

        $description = $this->askQuestion('Give a brief description of the model', "", false);

        $className = $this->askQuestion('What class name should this model have?', $modelName);

        $repositoryName = $this->askQuestion('What repository name should this model have?', 'tbl' . $modelName);

        $uniqueIdentifierName = $this->askQuestion('What name should the model\'s unique identifier column have?', $modelName . 'ID');

        $columns = [];
        $maxLengths = [];
        $columnTypeNames = array_merge(array_keys(self::$columnTypes), array_keys(self::$columnTypesWithMaxLength));
        while (true) {
            $columnName = $this->askQuestion('Enter the name of a column to add, or leave blank to finish adding columns:', null, false);

            if (!$columnName) {
                break;
            }

            $columns[$columnName] = $this->askChoiceQuestion('What type should the column have?', $columnTypeNames);

            if (isset(self::$columnTypesWithMaxLength[$columns[$columnName]])) {
                $maxLengths[$columnName] = $this->askQuestion('What maximum length should the column have?', 50);
            }
        }

        $schemaFileName = $reflector->getFileName();
        $fileName = dirname($schemaFileName) . '/' . str_replace('\\', '/', $subNamespace) . '/' . $className . '.php';

        self::writeClassContent($modelName, $description, $className, $namespace, $fileName, $repositoryName, $uniqueIdentifierName, $columns, $maxLengths);

        $this->writeNormal("Model created.", true);
        // todo: add model into SolutionSchema
    }

    private static function writeClassContent($modelName, $description, $className, $namespace, $fileName, $repositoryName, $uniqueIdentifierName, $columns, $maxLengths)
    {
        $columnTypes = array_merge(self::$columnTypes, self::$columnTypesWithMaxLength);
        $columnTypes["AutoIncrement"] = AutoIncrementColumn::class;

        $description = trim($modelName . " model. " . $description);
        $description = str_replace("\n", "\n * ", wordwrap($description));

        $repositoryClass = Repository::getDefaultRepositoryClassName();

        // Assume the label column is the 2nd column
        next($columns);
        $labelColumnName = key($columns);

        // Add an AutoIncrement column with the UniqueIdentifierName at the start of the columns
        $columnDefinitions = array_merge([$uniqueIdentifierName => "AutoIncrement"], $columns);

        // Build up imports, @property definitions, and column instance creation
        $imports = [];
        $columns = [];
        $properties = [];
        foreach ($columnDefinitions as $columnName => $columnShortClass) {
            $columnFullClass = $columnTypes[$columnShortClass];
            $imports[] = "use $columnFullClass;";

            if (isset($maxLengths[$columnName])) {
                $column = new $columnFullClass($columnName, $maxLengths[$columnName]);
                $columns[] = "new $columnShortClass('$columnName', $maxLengths[$columnName])";
            } else {
                $column = new $columnFullClass($columnName);
                $columns[] = "new $columnShortClass('$columnName')";
            }

            /** @var Column $column */
            $column = $column->getRepositorySpecificColumn($repositoryClass);
            $properties[] = ' * @property ' . $column->getPhpType() . ' $' . $columnName;
        }

        $imports = implode("\n", array_unique($imports));
        $columns = implode(",\n            ", $columns);
        $properties = implode("\n", $properties);

        $dirName = dirname($fileName);
        if (!file_exists($dirName)) {
            mkdir($dirName, 0777, true);
        }

        file_put_contents(
            $fileName,
            <<<PHP
<?php

namespace $namespace;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\ModelSchema;
$imports

/**
 * $description
 *
$properties
 */
class $className extends Model
{
    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        \$schema = new ModelSchema('$repositoryName');
        \$schema->addColumn(
            $columns
        );

        \$schema->labelColumnName = '$labelColumnName';

        return \$schema;
    }
}

PHP
        );
    }
}

<?php

namespace Rhubarb\Stem\Custard;

use phpDocumentor\Reflection\DocBlock;
use Rhubarb\Crown\Context;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Schema\Columns\Boolean;
use Rhubarb\Stem\Schema\Columns\Date;
use Rhubarb\Stem\Schema\Columns\DateTime;
use Rhubarb\Stem\Schema\Columns\Decimal;
use Rhubarb\Stem\Schema\Columns\Float;
use Rhubarb\Stem\Schema\Columns\ForeignKey;
use Rhubarb\Stem\Schema\Columns\Integer;
use Rhubarb\Stem\Schema\Columns\LongString;
use Rhubarb\Stem\Schema\Columns\Money;
use Rhubarb\Stem\Schema\Columns\String;
use Rhubarb\Stem\Schema\Columns\Time;
use Rhubarb\Stem\Schema\SolutionSchema;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateModelCommand extends CustardCommand
{
    protected static $columnTypes = [
        "Boolean" => Boolean::class,
        "Integer" => Integer::class,
        "ForeignKey" => ForeignKey::class,
        "Float" => Float::class,
        "Decimal" => Decimal::class,
        "Money" => Money::class,
        "Date" => Date::class,
        "DateTime" => DateTime::class,
        "Time" => Time::class,
        "String" => String::class,
        "LongString" => LongString::class,
    ];

    protected function configure()
    {
        $this->setName('stem:create-model')
            ->setDescription('Create a model class and add it to the schema')
            ->addArgument('schema', InputArgument::OPTIONAL, 'The name of the schema to create the model in');
    }

    const SETTINGS_PATH = "default-schema.txt";

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $schemaName = $input->getArgument('schema');

        // All schemas have to be looked up before getting 1, so that they are in the correct override order
        $schemaNames = array_keys(SolutionSchema::getAllSchemas());

        $helper = $this->getHelper('question');

        if (!$schemaName) {
            // Ask for schema name if it wasn't provided in arguments

            if (file_exists(self::SETTINGS_PATH)) {
                $schemaName = file_get_contents(self::SETTINGS_PATH);
            }

            $default = $schemaName ? '(' . $schemaName . ') ' : '';
            $question = new Question("<question>What schema do you want to add this model to? (" . implode(", ", $schemaNames) . ")</question> $default", $schemaName);

            $schemaName = $helper->ask($input, $output, $question);
        }

        if (!in_array($schemaName, $schemaNames)) {
            $this->writeNormal("<error>Couldn't find schema named '$schemaName'</error>");
            throw new \Exception("No schema selected");
        }

        $context = new Context();

        if ($context->DeveloperMode) {
            $output->writeln("Storing default schema in " . realpath(self::SETTINGS_PATH));

            // Store default schema to make it faster next time
            file_put_contents(self::SETTINGS_PATH, $schemaName);
        }

        try {
            $schema = SolutionSchema::getSchema($schemaName);
        } catch (SchemaNotFoundException $ex) {
            $output->writeln("<error>Couldn't find schema named '$schemaName'</error>");
            throw new \Exception("No schema selected");
        } catch (SchemaRegistrationException $ex) {
            $output->writeln("<error>Schema registered as '$schemaName' is not a SolutionSchema</error>");
            throw new \Exception("No schema selected");
        }

        $reflector = new \ReflectionClass($schema);

        $namespaceBase = $reflector->getNamespaceName().'\\';

        $question = new Question("<question>What namespace should this model be in?</question> $namespaceBase", "");
        $subNamespace = $helper->ask($input, $output, $question);
        $namespace = trim($namespaceBase . $subNamespace, '\\');

        $question = new Question("<question>What name should this model have?</question> ");
        $modelName = $helper->ask($input, $output, $question);

        $question = new Question("<question>What class name should this model have?</question> ($modelName) ", $modelName);
        $className = $helper->ask($input, $output, $question);

        $tableName = "tbl".$modelName;
        $question = new Question("<question>What repository name should this model have?</question> ($tableName) ", $tableName);
        $tableName = $helper->ask($input, $output, $question);

        $uniqueIdentifierName = $modelName."ID";
        $question = new Question("<question>What name should the model's unique identifier field?</question> ($uniqueIdentifierName) ", $uniqueIdentifierName);
        $uniqueIdentifierName = $helper->ask($input, $output, $question);

        $fieldNameQuestion = new Question("<question>Enter the name of a field to add, or leave blank to finish adding fields</question> ");
        $fieldTypeQuestion = new Question("<question>What type should the field have? (" . implode(", ", array_keys(self::$columnTypes)) . "</question> ");

        $fields = [];
        while (true) {
            $fieldName = $helper->ask($input, $output, $fieldNameQuestion);

            if (!$fieldName) {
                break;
            }

            $fieldType = null;
            while (!in_array($fieldType, self::$columnTypes)) {
                $fieldType = $helper->ask($input, $output, $fieldTypeQuestion);
            }

            $fields[$fieldName] = $fieldType;
        }

        $schemaFileName = $reflector->getFileName();
        // todo: write model class file to correct directory (relative to $schemaFileName and the $subNamespace
        // todo: add model into SolutionSchema
    }
}
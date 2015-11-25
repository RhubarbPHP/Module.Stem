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
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use WeAreVertigo\LoginProviders\AdminLoginProvider;

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

    /** @var QuestionHelper */
    protected $questionHelper;

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;

    protected function configure()
    {
        $this->setName('stem:create-model')
            ->setDescription('Create a model class and add it to the schema');
    }

    const SETTINGS_PATH = "settings/default-schema.txt";

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $schema = $this->getSchema();

        $reflector = new \ReflectionClass($schema);

        $namespaceBase = $reflector->getNamespaceName().'\\';

        $subNamespace = $this->askQuestion("<question>What namespace should this model be in?</question> $namespaceBase", null, false);
        $namespace = trim($namespaceBase . $subNamespace, '\\');

        $modelName = $this->askQuestion('What name should this model have?');

        $className = $this->askQuestion('What class name should this model have?', $modelName);

        $tableName = $this->askQuestion('What repository name should this model have?', 'tbl' . $modelName);

        $uniqueIdentifier = $this->askQuestion('What name should the model\'s unique identifier field have?', $modelName . 'ID');

        $fields = [];
        $columnTypeNames = array_keys(self::$columnTypes);
        while (true) {
            $fieldName = $this->askQuestion('Enter the name of a field to add, or leave blank to finish adding fields:', null, false);

            if (!$fieldName) {
                break;
            }

            $fields[$fieldName] = $this->askChoiceQuestion('What type should the field have?', $columnTypeNames);
        }

        $schemaFileName = $reflector->getFileName();
        // todo: write model class file to correct directory (relative to $schemaFileName and the $subNamespace
        // todo: add model into SolutionSchema
    }

    /**
     * @param string|Question $question Question text or a Question object
     * @param null|string $default The default answer
     * @param bool|\Closure $requireAnswer True for not-empty validation, or a closure for custom validation
     * @return string User's answer
     */
    private function askQuestion($question, $default = null, $requireAnswer = true)
    {
        if (!$this->questionHelper) {
            $this->questionHelper = $this->getHelper("question");
        }

        if (!($question instanceof Question)) {
            if (strpos($question, '<question>') === false) {
                $question = '<question>'.$question.'</question> ';
            }
            if ($default !== null) {
                $question .= "($default) ";
            }
            $question = new Question($question, $default);
        }

        if (is_callable($requireAnswer)) {
            $question->setValidator($requireAnswer);
        } elseif ($requireAnswer) {
            $question->setValidator(function ($answer) {
                if (trim($answer) == '') {
                    throw new \Exception(
                        'You must provide an answer to this question'
                    );
                }
                return $answer;
            });
        }

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    /**
     * @param string $question Question text
     * @param array $choices An array of choices which are acceptable answers
     * @param null|int $default The array index in $choices of the default answer
     * @param bool|\Closure $requireAnswer True for not-empty validation, or a closure for custom validation
     * @return string User's answer
     */
    private function askChoiceQuestion($question, array $choices, $default = null, $requireAnswer = true)
    {
        if (!($question instanceof Question)) {
            if (strpos($question, '<question>') === false) {
                $question = '<question>' . $question . '</question> ';
            }
            if ($default !== null) {
                $question .= "($choices[$default]) ";
            }
            $question = new ChoiceQuestion($question, $choices, $default);
        }

        if ($requireAnswer && !is_callable($requireAnswer)) {
            $requireAnswer = function ($answer) use ($choices) {
                if (trim($answer) == '') {
                    throw new \Exception(
                        'You must provide an answer to this question'
                    );
                }

                if (is_numeric($answer)) {
                    if (!isset($choices[$answer])) {
                        throw new \Exception("\"$answer\" is not a supported option index");
                    }
                    $answer = $choices[$answer];
                } elseif (!in_array($answer, $choices)) {
                    throw new \Exception("\"$answer\" is not a supported option");
                }

                return $answer;
            };
        }

        return $this->askQuestion($question, null, $requireAnswer);
    }

    /**
     * @return SolutionSchema
     */
    private function getSchema()
    {
        // All schemas have to be looked up before getting 1, so that they are in the correct override order
        $schemaNames = array_keys(SolutionSchema::getAllSchemas());

        $validator = function ($answer) use ($schemaNames) {
            if (is_numeric($answer)) {
                if (!isset($schemaNames[$answer])) {
                    throw new \Exception("Couldn't find schema at index \"$answer\"");
                }
                $schemaName = $schemaNames[$answer];
            } else {
                if (!in_array($answer, $schemaNames)) {
                    throw new \Exception("Couldn't find schema named \"$answer\"");
                }
                $schemaName = $answer;
            }

            try {
                $schema = SolutionSchema::getSchema($schemaName);
            } catch (SchemaNotFoundException $ex) {
                throw new \Exception("Couldn't find schema named \"$schemaName\"");
            } catch (SchemaRegistrationException $ex) {
                throw new \Exception("Schema registered as  \"$schemaName\" is not a SolutionSchema");
            }

            $context = new Context();

            if ($context->DeveloperMode) {
                // Store default schema to make it faster next time
                file_put_contents(self::SETTINGS_PATH, $schemaName);
                $this->output->writeln("Stored default schema \"$schemaName\" in " . realpath(self::SETTINGS_PATH));
            }

            return $schema;
        };

        if (file_exists(self::SETTINGS_PATH)) {
            $schemaName = file_get_contents(self::SETTINGS_PATH);
            $defaultSchemaIndex = array_search($schemaName, $schemaNames) ?: null;
        } else {
            $defaultSchemaIndex = null;
        }

        return $this->askChoiceQuestion('What schema do you want to add this model to?', $schemaNames, $defaultSchemaIndex, $validator);
    }
}
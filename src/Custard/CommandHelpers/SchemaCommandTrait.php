<?php

namespace Rhubarb\Stem\Custard\CommandHelpers;

use Rhubarb\Crown\Application;
use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Schema\SolutionSchema;

trait SchemaCommandTrait
{
    protected static $SETTINGS_PATH = __DIR__ . "../../../../../../../settings/default-schema.txt";

    /**
     * @return SolutionSchema
     */
    protected function getSchema($questionText = 'Enter schema name:')
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

            $application = Application::current();

            if ($application->developerMode) {
                // Store default schema to make it faster next time
                file_put_contents(self::$SETTINGS_PATH, $schemaName);
                $this->output->writeln("Stored default schema \"$schemaName\" in " . realpath(self::$SETTINGS_PATH));
            }

            return $schema;
        };

        if (file_exists(self::$SETTINGS_PATH)) {
            $schemaName = file_get_contents(self::$SETTINGS_PATH);
            $defaultSchemaIndex = array_search($schemaName, $schemaNames) ?: null;
        } else {
            $defaultSchemaIndex = null;
        }

        return $this->askChoiceQuestion($questionText, $schemaNames, $defaultSchemaIndex, $validator);
    }
}

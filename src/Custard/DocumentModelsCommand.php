<?php

namespace Rhubarb\Stem\Custard;

use phpDocumentor\Reflection\DocBlock;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Stem\Custard\CommandHelpers\ReflectionModel;
use Rhubarb\Stem\Custard\CommandHelpers\SchemaCommandTrait;
use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocumentModelsCommand extends CustardCommand
{
    use SchemaCommandTrait;

    protected function configure()
    {
        $this->setName('stem:document-models')
            ->setDescription('Generate phpDoc comments for Rhubarb Stem models, describing their fields and relationships')
            ->addArgument('schema', InputArgument::OPTIONAL, 'The name of the schema to scan models in')
            ->addOption('update-existing', 'u', InputOption::VALUE_NONE, 'If set, the definitions for properties already declared ' .
                'in the docblock will be rewritten, otherwise they will be left untouched')
            ->addOption('remove-old', 'r', InputOption::VALUE_NONE, 'If set, the definitions for properties already declared ' .
                'in the docblock which are no longer found in the model will be removed')
            ->addOption('rewrite-descriptions', 'd', InputOption::VALUE_NONE, 'If not set, existing descriptions on properties ' .
                'will be retained. If set, they will be overwritten by generated comments');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $schemaName = $input->getArgument('schema');

        if ($schemaName) {
            try {
                $schema = SolutionSchema::getSchema($schemaName);
            } catch (SchemaNotFoundException $ex) {
                $output->writeln("<error>Couldn't find schema named '$schemaName'</error>");
                return;
            } catch (SchemaRegistrationException $ex) {
                $output->writeln("<error>Schema registered as '$schemaName' is not a SolutionSchema</error>");
                return;
            }
        } else {
            $schema = $this->getSchema('What schema do you want to scan models in?');
        }

        $updateExisting = (bool)$input->getOption('update-existing');
        $removeOld = (bool)$input->getOption('remove-old');
        $rewriteDescriptions = (bool)$input->getOption('rewrite-descriptions');

        SolutionSchema::getAllSchemas();

        $changedModels = 0;

        foreach ($schema->getAllModels() as $modelName => $modelClass) {
            $this->writeVerbose("Processing $modelName... ");
            /** @var Model $model */
            $model = new $modelClass();

            $reflectionClass = new ReflectionModel($model);

            $changed = $reflectionClass->updateDocBlock($updateExisting, $removeOld, $rewriteDescriptions);

            if ($changed) {
                $this->writeVerbose("changes detected, writing to " . $reflectionClass->getFileName(), true);
                $changedModels++;

                $reflectionClass->writePhpDoc();
            } else {
                $this->writeVerbose("no changes detected, moving on", true);
            }
        }

        $this->writeNormal("$changedModels model phpDoc comments updated, finished", true);
    }
}

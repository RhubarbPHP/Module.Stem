<?php

namespace Rhubarb\Stem\Custard;

use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Stem\Exceptions\SchemaNotFoundException;
use Rhubarb\Stem\Exceptions\SchemaRegistrationException;
use Rhubarb\Stem\Schema\SolutionSchema;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class UpdateRepositorySchemasCommand extends RequiresRepositoryCommand
{
    protected function configure()
    {
        $this->setName('stem:update-schemas')
            ->setDescription('Updates the repository schemas to match those of the registered models')
            ->addArgument('schema', InputArgument::OPTIONAL, 'The name of the schema to update');
    }

    protected function executeWithConnection(InputInterface $input, OutputInterface $output)
    {
        parent::executeWithConnection($input, $output);

        $schemaName = $input->getArgument('schema');

        if ($schemaName != null) {
            try {
                $schema = SolutionSchema::getSchema($schemaName);
            } catch (SchemaNotFoundException $ex) {
                $output->writeln("<error>Couldn't find schema named '$schemaName'</error>");
                return;
            } catch (SchemaRegistrationException $ex) {
                $output->writeln("<error>Schema registered as '$schemaName' is not a SolutionSchema</error>");
                return;
            }

            $this->updateSchema($schema);
        } else {
            $schemas = SolutionSchema::getAllSchemas();

            $progress = new ProgressBar($output, sizeof($schemas));

            foreach ($schemas as $schema) {
                $progress->advance();
                $this->updateSchema($schema);
            }

            $progress->finish();
        }
    }

    private function updateSchema(SolutionSchema $schema)
    {
        $this->writeNormal(" Processing schema ".get_class($schema), true);
        $schema->checkModelSchemas();
    }
}

<?php

namespace Rhubarb\Stem\Custard;

use Rhubarb\Stem\Schema\SolutionSchema;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedDemoDataCommand extends RequiresRepositoryCommand
{
    protected function configure()
    {
        $this->setName('stem:seed-data')
            ->setDescription('Seeds the repositories with demo data');
    }

    /**
     * @var DemoDataSeederInterface[]
     */
    private static $seeders = [];

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $this->writeNormal("Clearing existing data.", true);

        $schemas = SolutionSchema::getAllSchemas();
        $modelSchemas = [];

        foreach( $schemas as $schema ){
            $modelSchemas = array_merge($modelSchemas,$schema->getAllModels());
        }

        $progressBar = new ProgressBar($output, sizeof($modelSchemas));

        foreach($modelSchemas as $alias => $modelClass ){
            $progressBar->advance();

            $model = new $modelClass();
            $repository = $model->getRepository();

            $this->writeNormal(" Truncating ".str_pad(basename($repository->getSchema()->schemaName), 50, ' ', STR_PAD_RIGHT ));

            $repository->clearRepositoryData();
        }

        $this->writeNormal("", true);
        $this->writeNormal("", true);

        $this->writeNormal("Running seed scripts...", true);

        $progressBar->finish();

        $progressBar = new ProgressBar($output, sizeof($modelSchemas));

        foreach(self::$seeders as $seeder){
            $progressBar->advance();

            $this->writeNormal(" Processing ".str_pad(basename(str_replace("\\", "/", get_class($seeder))), 50, ' ', STR_PAD_RIGHT ));

            $seeder->seedData($output);
        }

        $progressBar->finish();

        $this->writeNormal("", true);
        $this->writeNormal("", true);

        $this->writeNormal("Seeding Complete", true);
    }

    public static function registerDemoDataSeeder(DemoDataSeederInterface $demoDataSeeder)
    {
        self::$seeders[] = $demoDataSeeder;
    }
}
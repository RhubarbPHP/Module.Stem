<?php

namespace Rhubarb\Stem\Custard;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\SolutionSchema;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedDemoDataCommand extends RequiresRepositoryCommand
{
    protected function configure()
    {
        $this->setName('stem:seed-data')
            ->setDescription('Seeds the repositories with demo data')
            ->addOption("list", "l", null, "Lists the seeders available")
            ->addOption("obliterate", "o", InputOption::VALUE_NONE, "Obliterate the entire database first")
            ->addOption("bulk", "b", InputOption::VALUE_NONE, "Include bulk seed sets")
            ->addOption("force", "f", InputOption::VALUE_NONE, "Forces obliteration even if running only a single seeder")
            ->addArgument("seeder", InputArgument::OPTIONAL, "The name of the seeder to run, leave out for all");

        parent::configure();
    }

    /**
     * @var DemoDataSeederInterface[]
     */
    private static $seeders = [];

    private static $enableTruncating = false;

    /**
     * True if we're presently seeding the database
     * 
     * Used to stop event chains based of model chains that the seeder should not cause.
     * e.g. user activation emails
     */
    public static $seeding = false;

    protected function executeWithConnection(InputInterface $input, OutputInterface $output)
    {
        SeedDemoDataCommand::$seeding = true;

        $output->getFormatter()->setStyle('bold', new OutputFormatterStyle(null, null, ['bold']));
        $output->getFormatter()->setStyle('blink', new OutputFormatterStyle(null, null, ['blink']));
        $output->getFormatter()->setStyle('critical', new OutputFormatterStyle('red', null, ['bold']));

        if ($input->getOption("list") != null) {

            $output->writeln("Listing possible seeders:");
            $output->writeln("");

            foreach (self::$seeders as $seeder) {
                $output->writeln("\t" . basename(str_replace("\\", "/", get_class($seeder))));
            }

            $output->writeln("");
            return;
        }

        $chosenSeeder = $input->getArgument("seeder");

        if (($input->getOption("obliterate") === true) && (!empty($chosenSeeder))) {
            // Running a single seeder after an obliteration makes no sense - this is probably
            // a mistake.
            if ($input->getOption("force") === false) {
                $output->writeln("<critical>Running a single seeder after obliteration is probably not sane. Use with -f to force.");
                return;
            }
        }

        parent::executeWithConnection($input, $output);

        $this->writeNormal("Updating table schemas...", true);

        $schemas = SolutionSchema::getAllSchemas();
        foreach ($schemas as $schema) {
            $schema->checkModelSchemas();
        }

        if (($input->getOption("obliterate") === true) || self::$enableTruncating) {

            $this->writeNormal("Clearing existing data.", true);

            $modelSchemas = [];

            foreach ($schemas as $schema) {
                $modelSchemas = array_merge($modelSchemas, $schema->getAllModels());
            }

            $progressBar = new ProgressBar($output, sizeof($modelSchemas));

            foreach ($modelSchemas as $alias => $modelClass) {
                $progressBar->advance();

                /** @var Model $model */
                $model = new $modelClass();
                $schema = $model->getSchema();

                $repository = $model->getRepository();

                $this->writeNormal(" Truncating " . str_pad(basename($schema->schemaName), 50, ' ', STR_PAD_RIGHT));

                $repository->clearRepositoryData();
            }

            $progressBar->finish();

            $this->writeNormal("", true);
            $this->writeNormal("", true);
        }

        $this->writeNormal("Running seed scripts...", true);

        $includeBulk = ($input->getOption("bulk") === true);

        if ($chosenSeeder) {
            $found = false;
            foreach (self::$seeders as $seeder) {
                if (strtolower(basename(str_replace("\\", "/", get_class($seeder)))) == strtolower($chosenSeeder)) {
                    $this->writeNormal(" Processing " . str_pad(basename(str_replace("\\", "/", get_class($seeder))), 50, ' ', STR_PAD_RIGHT));
                    $output->writeln(['', '']);

                    if ($seeder instanceof DescribedDemoDataSeederInterface) {
                        $seeder->describeDemoData($output);
                    }

                    $seeder->seedData($output, $includeBulk);
                    $found = true;
                }
            }

            if (!$found) {
                $output->writeln("No seeder matching `" . $chosenSeeder . "`");
                $this->writeNormal("", true);

                return;
            }
        } else {
            $progressBar = new ProgressBar($output, sizeof(self::$seeders));

            foreach (self::$seeders as $seeder) {
                $progressBar->advance();

                $this->writeNormal(" Processing " . str_pad(basename(str_replace("\\", "/", get_class($seeder))), 50, ' ', STR_PAD_RIGHT));

                $seeder->seedData($output, $includeBulk);
            }

            $progressBar->finish();
        }

        $this->writeNormal("", true);
        $this->writeNormal("<info>Seeding Complete</info>", true);

        SeedDemoDataCommand::$seeding = false;
    }

    public static function setEnableTruncating($enableTruncating)
    {
        self::$enableTruncating = $enableTruncating;
    }

    public static function registerDemoDataSeeder(DemoDataSeederInterface $demoDataSeeder)
    {
        self::$seeders[] = $demoDataSeeder;
    }
}

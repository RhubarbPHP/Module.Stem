<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Output\OutputInterface;

abstract class ScenarioDataSeeder implements DemoDataSeederInterface
{
    private static $scenarioCount = 1;

    private static $alreadyRan = [];

    /**
     * @return array|DemoDataSeederInterface[]
     */
    protected function getPreRequisiteSeeders()
    {
        return [];
    }

    public function seedData(OutputInterface $output, $includeBulk = false)
    {
        $class = get_class($this);
        if (in_array($class, self::$alreadyRan)){
            return;
        }

        self::$alreadyRan[] = $class;

        foreach($this->getPreRequisiteSeeders() as $seeder){
            $seeder->seedData($output);
        }

        foreach ($this->getScenarios() as $scenario) {
            if ($includeBulk || !($scenario instanceof BulkScenario)) {
                $output->writeln("");
                $output->writeln("Scenario " . self::$scenarioCount . ": " . $scenario->getName());
                $output->writeln("");
                $scenario->run($output);
                self::$scenarioCount++;
            }
        }
    }

    /**
     * @return Scenario[]
     */
    abstract function getScenarios(): array;
}
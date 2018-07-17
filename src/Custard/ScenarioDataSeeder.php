<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Output\OutputInterface;

abstract class ScenarioDataSeeder implements DemoDataSeederInterface
{
    public function seedData(OutputInterface $output)
    {
        $scenarioNumber = 1;
        foreach ($this->getScenarios() as $scenario) {
            $output->writeln("");
            $output->writeln("Scenario $scenarioNumber: " . $scenario->getName());
            $output->writeln("");
            $scenario->run($output);
            $scenarioNumber++;
        }
    }

    /**
     * @return Scenario[]
     */
    abstract function getScenarios(): array;
}
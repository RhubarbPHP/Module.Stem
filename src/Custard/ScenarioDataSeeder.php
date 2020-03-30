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

    /**
     * A hook for logic to run before each scenario
     * @param Scenario $scenario
     */
    protected function beforeScenario(Scenario $scenario)
    {

    }

    /**
     * A hook for logic to run after each scenario
     * @param Scenario $scenario
     */
    protected function afterScenario(Scenario $scenario)
    {

    }

    public function seedData(OutputInterface $output, $includeBulk = false)
    {
        $class = get_class($this);
        if (in_array($class, self::$alreadyRan)){
            return;
        }

        self::$alreadyRan[] = $class;

        foreach($this->getPreRequisiteSeeders() as $seeder){
            if (is_string($seeder)) {
                $seeder = new $seeder();
            }

            if (!($seeder instanceof DemoDataSeederInterface)){
                throw new \InvalidArgumentException(get_class($seeder)." does not extend DemoDataSeederInterface.");
            }

            $seeder->seedData($output, $includeBulk);
        }

        foreach ($this->getScenarios() as $scenario) {
            if ($includeBulk || !($scenario instanceof BulkScenario)) {
                $this->beforeScenario($scenario);

                $output->writeln("");
                $output->writeln("<comment>Scenario " . self::$scenarioCount . ": <bold>" . $scenario->getName().'</bold></comment>');
                $output->writeln(str_repeat('-', 11 + strlen(self::$scenarioCount) + strlen($scenario->getName())));
                $scenario->run($output);
                self::$scenarioCount++;

                $this->afterScenario($scenario);
            }
        }
    }

    /**
     * @return Scenario[]
     */
    abstract function getScenarios(): array;
}

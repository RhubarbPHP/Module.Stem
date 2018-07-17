<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Output\OutputInterface;

class Scenario
{
    /**
     * @var callable $seedScenario
     */
    private $seedScenario;

    /**
     * @var ScenarioDescription $scenarioDescription
     */
    private $scenarioDescription;

    /**
     * @var string
     */
    private $name;

    /**
     * Scenario constructor.
     * @param string $name of the scenario
     * @param callable $seedScenario callback, a ScenarioDescription will be given as the first parameter
     *
     */
    public function __construct(string $name, callable $seedScenario)
    {
        $this->seedScenario = $seedScenario;
        $this->scenarioDescription = new ScenarioDescription();
        $this->name = $name;
    }

    /**
     * @param OutputInterface $output
     * Run the data seeder and describe what happened to $output
     */
    public function run(OutputInterface $output)
    {
        $seedScenario = $this->seedScenario;
        $seedScenario($this->scenarioDescription);
        $this->scenarioDescription->describe($output);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
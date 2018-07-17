<?php

namespace Rhubarb\Stem\Custard;
use Symfony\Component\Console\Output\OutputInterface;

class ScenarioDescription
{
    private $lines = [];

    public function writeLine(string $line)
    {
        $this->lines[] = "   " . $line;
        return $this;
    }

    public function describe(OutputInterface $output)
    {
        $output->writeln($this->lines);
    }
}
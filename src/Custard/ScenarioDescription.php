<?php

namespace Rhubarb\Stem\Custard;
use Symfony\Component\Console\Output\OutputInterface;

class ScenarioDescription
{
    /**
     * @var string[] message lines to write
     */
    private $lines = [];

    /**
     * @param string $line to write
     * @param string $spacer indentation 
     * @return $this for the fluent pattern
     */
    public function writeLine(string $line, $spacer = "   ")
    {
        $this->lines[] = $spacer . $line;
        return $this;
    }

    /**
     * @param OutputInterface $output
     * Write all oif the lines
     */
    public function describe(OutputInterface $output)
    {
        $output->writeln($this->lines);
    }
}
<?php

namespace Rhubarb\Stem\Custard;
use Symfony\Component\Console\Output\OutputInterface;

class ScenarioDescription
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param string $line to write
     * @param string $spacer indentation
     * @return $this for the fluent pattern
     */
    public function writeLine(string $line, $spacer = "   ")
    {
        $string = $spacer . $line;
        $this->output->writeln($string);
        return $this;
    }

    /**
     * @param OutputInterface $output
     * @return $this;
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }
}

<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Output\OutputInterface;

interface DemoDataSeederInterface
{
    public function seedData(OutputInterface $output, $includeBulk = false);
}

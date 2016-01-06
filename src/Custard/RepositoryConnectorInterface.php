<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface RepositoryConnectorInterface
{
    public function interact(InputInterface $input, OutputInterface $output, QuestionHelper $helper);
}

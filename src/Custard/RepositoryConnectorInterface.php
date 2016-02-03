<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface RepositoryConnectorInterface
{
    /**
     * Called when an output interface is available to ask users for the settings
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $helper
     * @return mixed
     */
    public function interact(InputInterface $input, OutputInterface $output, QuestionHelper $helper);

    /**
     * Called during configuration of the demo data seeder
     * @param Command $command
     * @return mixed
     */
    public function configure(Command $command);

    /**
     * Called to make the connection
     * @param InputInterface $input
     * @return mixed
     */
    public function connect(InputInterface $input);
}

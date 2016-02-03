<?php

namespace Rhubarb\Stem\Custard;

use Rhubarb\Custard\Command\CustardCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class RequiresRepositoryCommand extends CustardCommand
{
    /**
     * @var OutputInterface
    */
    protected $output;

    /**
     * @var RepositoryConnectorInterface
     */
    private static $repositoryConnector;

    protected final function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (self::$repositoryConnector) {
            self::$repositoryConnector->connect($input, $output);
        }

        $this->executeWithConnection($input, $output);
    }

    protected function executeWithConnection(InputInterface $input, OutputInterface $output)
    {

    }

    /**
     * Sets a repository connector
     *
     * Traditionally this is called from the Module::getCustardCommands() function of the relevant module.
     *
     * @param RepositoryConnectorInterface $connector
     */
    public static function setRepositoryConnector(RepositoryConnectorInterface $connector)
    {
        self::$repositoryConnector = $connector;
    }

    /**
     * Interacts with the user.
     *
     * This method is executed before the InputDefinition is validated.
     * This means that this is the only place where the command can
     * interactively ask for values of missing required arguments.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $helper = $this->getHelper('question');

        if (self::$repositoryConnector) {
            self::$repositoryConnector->interact($input, $output, $helper);
        }
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        if ( self::$repositoryConnector) {
            self::$repositoryConnector->configure($this);
        }

        parent::configure();
    }
}

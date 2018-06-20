<?php

namespace Rhubarb\Stem\Custard;

use Symfony\Component\Console\Output\Output;

interface DescribedDemoDataSeederInterface extends DemoDataSeederInterface
{
    /**
     * Describes the test scenarios provided by this seeder.
     *
     * The $output interface has a number of useful styles predefined:
     *
     * <critical> Red and bold
     * <bold> Bold
     * <blink> Blinking (not supported everywhere)
     *
     * Other options can be defined and the defaults provided by Symfony are
     * still present. You can read more at the link below:
     *
     * https://symfony.com/doc/current/console/coloring.html
     *
     * @param Output $output
     * @return mixed
     */
    public function describeDemoData(Output $output);
}

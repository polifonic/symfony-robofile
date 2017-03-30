<?php

namespace Polifonic\Robo\Task\Symfony;

use Robo\Task\Base\Exec;

class SymfonyConsoleTask extends Exec
{
    public function getCommand()
    {
        return sprintf(
            'php app/console %s',
            parent::getCommand()
        );
    }
}

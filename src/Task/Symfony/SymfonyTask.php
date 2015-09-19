<?php

namespace Polifonic\Robo\Task\Symfony;

use Robo\Result;
use Robo\Task\Base\Exec;

class SymfonyTask extends Exec
{
	public function getCommand()
    {
        return sprintf(
        	'php app/console %s',
        	parent::getCommand()
        );
    }
}
<?php

namespace Polifonic\Robo\Task\Symfony;

trait loadTasks
{
    public function cacheClear()
    {
        return $this->taskSymfony('cache:clear');
    }

    public function assetsInstall()
    {
        return $this->taskSymfony('assets:install');
    }

    /**
     * @param $command
     *
     * @return SymfonyTask
     */
    protected function taskSymfony($command)
    {
        return new SymfonyTask($command);
    }
}

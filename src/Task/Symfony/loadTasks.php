<?php

namespace Polifonic\Robo\Task\Symfony;

trait loadTasks
{
    public function taskSymfonyCacheClear()
    {
        return $this->taskSymfony('cache:clear');
    }

    public function taskSymfonyAssetsInstall()
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
        return $this->task(SymfonyConsoleTask::class, $command);
    }
}

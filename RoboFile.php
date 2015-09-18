<?php

require_once __DIR__.'/vendor/autoload.php';

use Robo\Tasks;

class RoboFile extends Tasks
{
	const OS_MACOS = 1;
	const OS_WINDOWS = 2;
	const OS_LINUX  = 3;
	const OS_OTHER = 4;

	function build()
	{
		$this->stopOnFail();

		$this->taskGitStack()
			->pull()
			->run();

		$this->taskComposerUpdate()
			->run();

		$this->taskExec('php app/console')
			->arg('cache:clear')
			->arg('--no-warmup')
			->arg('--env=dev')
			->run();

		$this->taskExec('php app/console')
			->arg('cache:clear')
			->arg('--no-warmup')
			->arg('--env=prod')
			->run();

		$this->taskExec('php app/console')
			->arg('propel:model:build')
			->run();

		if (self::OS_WINDOWS === $this->os()) {
			$this->taskExec('php app/console')
				->arg('assets:install')
				->run();
		} else {
			$this->taskExec('php app/console')
				->arg('assets:install')
				->arg('--symlink')
				->run();
		}
	}

	public function os()
	{
		$uname = strtolower(php_uname());

		if (strpos($uname, "darwin") !== false) {
		    $os = self::OS_MACOS;
		} else if (strpos($uname, "win") !== false) {
		    $os = self::OS_WINDOWS;
		} else if (strpos($uname, "linux") !== false) {
		    $os = self::OS_LINUX;
		} else {
			$os = self::OS_OTHER;
		}

		return $os;
	}

	public function propelDiff()
	{
		$this->taskSymfonyCommand('propel:migration:generate-diff')
			->run();
	}

	public function propelMigrate()
	{
		$this->taskSymfonyCommand('propel:migration:migrate')
			->run();
	}

	public function clean()
    {
        $this->taskCleanDir([
            'app/cache',
            'app/logs'
        ])->run();

        $this->taskDeleteDir([
            'web/assets/tmp_uploads',
        ])->run();
    }

    public function versionShow()
    {
    	$version = Version::VERSION;

    	$this->say($version);
    }

    public function versionBump($version = '')
    {
        if (!$version) {
            $versionParts = explode('.', Version::VERSION);
            $versionParts[count($versionParts)-1]++;
            $version = implode('.', $versionParts);
        }

        $this->say("Bumping version to $version");

        $this->taskReplaceInFile('src/Version.php')
            ->from(Version::VERSION)
            ->to($version)
            ->run();
    }

    public function release()
    {
    	$this->stopOnFail();

    	$version = Version::VERSION;

    	$this->say('Current version: '.$version);

        $versionParts = explode('.', Version::VERSION);
        $versionParts[count($versionParts)-1]++;
        $version = implode('.', $versionParts);

        $this->say('Computing new version: '.$version);

        $this->say('Starting new release');

    	$this->taskExec('git flow release start')
    		->arg($version)
    		->run();

    	$this->say('Bumping up new release number to '.$version);

        $this->taskReplaceInFile('src/Version.php')
            ->from(Version::VERSION)
            ->to($version)
            ->run();

        $this->say('Committing');

		$this->taskGitStack()
            ->add('-A')
            ->commit("updated version number")
            ->run();

        $this->say('Finishing release '.$version);

        $this->taskExec('git flow release finish')
        	->arg('-m '.$version)
        	->arg($version)
        	->run();

        $this->say('Pushing to remote');

		$this->taskGitStack()
            ->push('origin', 'master')
            ->run();

		$this->taskGitStack()
            ->push('origin', 'develop')
            ->run();

		$this->taskGitStack()
            ->push('origin', '--tags')
            ->run();

        $this->taskExec('cap deploy')
        	->run();
    }
}

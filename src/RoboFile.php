<?php

namespace Polifonic\Robo;

use Robo\Tasks;

class RoboFile extends Tasks
{
    use \Polifonic\Robo\Task\Symfony\loadTasks;

    const OS_MACOS = 'OSX';
    const OS_WINDOWS = 'Windows';
    const OS_LINUX = 'Linux';
    const OS_OTHER = 'Other';

    public function assets()
    {
        if (self::OS_WINDOWS === $this->os()) {
            $this->taskSymfony('assets:install')
                ->run();
        } else {
            $this->taskSymfony('assets:install')
                ->option('symlink')
                ->run();
        }
    }

    /**
     * Builds app. Will run:
     * - composer install
     * - cache:clear command in both dev and prod environments
     * - propel:model:build
     * - assets:install
     */
    public function build()
    {
        $this->stopOnFail();

        $path_to_composer = null;
        
        if (self::OS_WINDOWS === $this->os()) {
            $path_to_composer = 'composer';
        }
        
        $this->taskComposerInstall($path_to_composer)
            ->run();

        $this->taskSymfony('cache:clear')
            ->option('no-warmup')
            ->option('env', 'dev')
            ->run();

        $this->taskSymfony('cache:clear')
            ->option('no-warmup')
            ->option('env', 'prod')
            ->run();

        $this->propelBuild();

        $this->assets();
    }

    /**
     * Builds app. Will run:
     * - composer update
     * - cache:clear command in both dev and prod environments
     * - propel:model:build
     * - propel:migration:migrate
     * - assets:install
     */
    public function buildAll()
    {
        $this->stopOnFail();

        $path_to_composer = null;
        
        if (self::OS_WINDOWS === $this->os()) {
            $path_to_composer = 'composer';
        }
        
        $this->taskComposerUpdate($path_to_composer)
            ->run();

        $this->taskSymfony('cache:clear')
            ->option('no-warmup')
            ->option('env', 'dev')
            ->run();

        $this->taskSymfony('cache:clear')
            ->option('no-warmup')
            ->option('env', 'prod')
            ->run();

        $this->propelBuild();

        $this->propelMigrate();

        $this->assets();
    }

    public function clean()
    {
        $this->taskCleanDir([
            'app/cache',
            'app/logs',
        ])->run();

        $this->taskDeleteDir([
            'web/assets/tmp_uploads',
        ])->run();
    }

    /**
     * Synchronizes local tags with remote. REmoves local tags not on remote.
     */
    public function gitSyncTags()
    {
        $this->taskExec('git tag -d $(git tag)')
            ->run();

        $this->taskExec('git fetch --tags')
            ->run();
    }

    /**
     * Determines current os.
     */
    public function os()
    {
        $uname = strtolower(php_uname());

        if (strpos($uname, 'darwin') !== false) {
            $os = self::OS_MACOS;
        } elseif (strpos($uname, 'win') !== false) {
            $os = self::OS_WINDOWS;
        } elseif (strpos($uname, 'linux') !== false) {
            $os = self::OS_LINUX;
        } else {
            $os = self::OS_OTHER;
        }

        return $os;
    }

    /**
     * Runs phpunit tests for app.
     */
    public function phpunit()
    {
        $this->stopOnFail();

        $this->taskExec('vendor/bin/phpunit -c app')
            ->run();
    }

    public function propelBuild()
    {
        $this->taskSymfony('propel:model:build')
            ->run();
    }

    public function propelMigrate()
    {
        $this->taskSymfony('propel:migration:migrate')
            ->run();
    }

    public function release()
    {
        $this->stopOnFail();

        $version = $this->getVersion();

        $this->say('Current version: '.$version);

        $version = $this->updateVersion();

        $this->say('Computing new version: '.$version);

        $this->say('Starting new release');

        $this->taskExec('git flow release start')
            ->arg($version)
            ->run();

        $this->say('Bumping up new release number to '.$version);

        $this->writeVersion($version);

        $this->say('Committing');

        $this->taskGitStack()
            ->add('-A')
            ->commit('updated version number')
            ->run();

        $this->say('Finishing release '.$version);

        $this->taskExec('git flow release finish')
            ->arg('-m '.$version)
            ->arg($version)
            ->run();

        $this->say('Pushing to remote');

        $this->taskGitStack()
            ->push('origin', 'master')
            ->push('origin', 'develop')
            ->push('origin', '--tags')
            ->run();

        $this->taskExec('cap deploy')
            ->run();
    }

    /**
     * Generates a new secret string.
     */
    public function secret()
    {
        $secret = hash('sha1', uniqid(mt_rand()));

        $this->say($secret);
    }

    public function versionShow()
    {
        $version = $this->getVersion();

        $this->say($version);
    }

    public function versionBump()
    {
        $version = $this->updateVersion();

        $this->say("Bumping version to $version");

        $this->writeVersion($version);
    }

    protected function getVersion()
    {
        return \Version::VERSION;
    }

    protected function writeVersion($version)
    {
        $this->taskReplaceInFile('src/Version.php')
            ->from(\Version::VERSION)
            ->to($version)
            ->run();
    }

    protected function updateVersion($version = null)
    {
        $version = $version ?: $this->getVersion();

        $versionParts = explode('.', $version);
        ++$versionParts[count($versionParts) - 1];
        $version = implode('.', $versionParts);

        return $version;
    }
}

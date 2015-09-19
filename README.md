# polifonic/symfony-robofile

A RoboFile for symfony apps.

This is the RoboFile that we use for building our own Symfony apps, and we are just making it available to the community. Obviouly it is geared to the way we do things (for example, we use Propel), but it is easy to extend, and hopefully at the very least it can serve as an example or inspiration for your own implementation.

The package also provides a custom `SymfonyTask` which is the equivalent of running `php app/console` on the command line. Note that this is not quite the same as the `SymfonyCommand` task provided by the original Robo package (which does not include the app kernel, for one thing, and therefore does not recognize the `env` option).

This package is totally independent of the [polifonic](http://www.polifonic.io) app.

## Installation

Add the package to your app's `composer.json` file. Depending on your needs, you may add it in the "require" section or the "require-dev" section.

```
    "require-dev": {
        ....
        "polifonic/symfony-robo": "*",
    }
```


## Usage

Create a new `RoboFile.php` in the root directory of your app. The class should extend the `Polifonic\Robo\RoboFile` class provided by the package. You can use the tasks defined in the original `RoboFile`, override them or add new tasks as per your requirements.

```php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Polifonic\Robo\RoboFile as Tasks;

class RoboFile extends Tasks
{
}
```

Then run tasks just like any other robo task, by typing the following on the command line:

```xterm
vendor/bin/robo <task>
```

If you have also installed robo in your path, you can type:

```xterm
robo <task>
```

## Tasks provided

The package's `RoboFile` class provides the following tasks:

### build

The `build` task will perform the following taks:

* `composer update`
* clear the cache in both `dev` and `prod` environments
* build propel model
* install assets

### release

The `release` task uses git-flow to create a new release, following the steps below. It assumes that git flow has been installed and initialized for this project.

* compute the incremented version number (see below)
* create a new git flow release named after thew incremented version number
* write the incremented version number to file
* commit the change
* finish the git flow release; it will be tagged with the incremented version number
* push both develop and master branches to the remote repo
* run `cap deploy`

### os

Displays the current OS as one of the following:

* OSX
* Windows
* Linux
* Other

This is used in the build task when running the `assets:install` command: if the OS is Windows, assets will be copied; otherwise, they will be symlinked.

## Version numbers

When dealing with version numbers, the package makes the following assumptions:

* A `Version` class exists under the `/src` directory
* The class defins a const named VERSION
* The value of Version::VERSION is a semver-copatible version number

If this is not suitable in your case, then you can overwrite the following methods in your `RoboFile`:

* `getVersion`: this method is responsible for returning the version number
* `writeVersion($version)`: this method is responsible for writing the version to file.

## The SymfonyTask

The SymfonyTask runs any symfony command via `php app/console` and will therefore support any symfony command available on the command line. It assumes that the package is used inside the root directory of a symfony app.
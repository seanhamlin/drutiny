# Working with Plugins

## Find plugins

You can search within composer for Drutiny plugins:
```
composer search drutiny
```

### Available Druitiny plugins


Plugin Name | Documentation | Description
--|--|--
Acquia | xxx | xxx

## Install plugins

### Install plugins via Packagist (default)



### Install plugins from a VCS repository

If you want extend Drutiny with some private features, you may want host this without composer and include a VCS repository directly.
<a href="https://getcomposer.org/doc/05-repositories.md#loading-a-package-from-a-vcs-repository">Read composer docs</a>

To do so, update the Drutiny composer.json and add your repository. 

```
{
    "require": {
        "vendor/my-private-repo": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@bitbucket.org:vendor/my-private-repo.git"
        }
    ]
}

{
    "require-dev": {
        "drutiny/drutiny": "2.x-dev",
        "drutiny/plugin-cs": "dev-Drutiny-Plugin"

    },
    "require": {
        "drutiny/plugin-drupal-8": "2.x-dev",
        "drutiny/plugin-drupal-7": "2.x-dev",
        "drutiny/http": "2.x-dev",
        "drutiny/acquia": "2.x-dev"
    },
    "repositories": [
        {
            "type": "vcs",
            "url":  "eecsteiger@svn-3224.prod.hosting.acquia.com:eecsteiger.git",
            "reference": "Drutiny-Plugin"

        }
    ]
}

```

## Creating a plugin

### Getting started
We strongly recommend to not change/ modify drutiny or its plugins directly, instead create a custom plugin and override or extend the existing functionallity.

### Create the plugin
As the minimum requirement for an plugin, we need a folder with the name of the plugin and some composer library definitions in a `composer.json` file.

```
{
    "name": "drutiny/plugin-cs",
    "type": "library",
    "description": "Christian Steiger's plugin for Drutiny",
    "keywords": ["drupal", "audit", "drush", "ssh", "report"],
    "authors": [
        {"name": "Christian Steiger", "email": "chr.steiger@gmail.com"}
    ],
    "require": {
        "drutiny/drutiny": "2.x-dev",
        "symfony/yaml": "^3.2"
    },
    "autoload": {
        "psr-4": {
            "Drutiny\\Plugin\\CS\\": "src/",
            "DrutinyTests\\Plugin\\CS\\": "tests/src/"
        }
    }
}
``` 


#### Create custom commands and configuration
Drutiny is extensible through a config file where you can add commands,
templates and targets to extend Drutiny's existing ones.

All extensions are registered through a file called `drutiny.config.yml` which
should be placed in the root of an extension/plugin library

Example for a new command:
```
Command:
  - Drutiny\Acquia\Command\SiteFactoryProfileRunCommand
```

Example for a new target:
```
```

Example for adding a template directory:
```
Template:
  - my-templates
```
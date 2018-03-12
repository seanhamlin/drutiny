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

```
composer require drutiny/acquia 2.x-dev
```

### Install plugins from a VCS repository

If you want extend Drutiny with some private features, you may want host this without composer and include a VCS repository directly.
<a href="https://getcomposer.org/doc/05-repositories.md#loading-a-package-from-a-vcs-repository">Read composer docs</a>

To do so, update the Drutiny composer.json and add your repository. 

```
{
    "require-dev": {
        "drutiny/plugin-name": "dev-Drutiny-Plugin"

    },
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@bitbucket.org:vendor/my-private-repo.git",
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
    "name": "drutiny/plugin-name",
    "type": "library",
    "description": "Plugin for Drutiny",
    "keywords": ["drupal", "audit", "drush", "ssh", "report"],
    "authors": [
        {"name": "Bruce Wayne", "email": "bruce.wayne@example.com"}
    ],
    "require": {
        "drutiny/drutiny": "2.x-dev",
        "symfony/yaml": "^3.2"
    },
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

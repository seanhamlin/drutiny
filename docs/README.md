Drutiny helps assess targets (e.g. Drupal sites) against a set of governing
policies. These policies are typically curated into a profile rendered by
Drutiny into a report (HTML, Markdown, JSON).

## Basic Usage
A "hello world" example would be to run the __test__ profile against an empty
target (Drush provides an empty target called "@none.").

```
drutiny profile:run test @none --format=html
```
The above command will produce an HTML report of the test profile.
*Note: the above command depends on Drush being installed globally.*

For more information on usage see [Profile Usage](Usage/Profile.md)

## Installation

`drutiny/drutiny` itself is a core engine for drutiny based CLI tools that may
incorporate their own integrations. These extended tools will typically have
their own installation methods (such as a supported phar file).

If you're installing Drutiny core, you'll be looking to utilize either the
limited policies that it comes with or be developing your own. The recommended
way to install Drutiny core is from source:

```
git clone --branch=3.0.x git@github.com:drutiny/drutiny.git
cd drutiny
composer install
```

This will also install the dev dependencies such as phpunit and phpcs.

Alternatively you can install using composer:

```
composer require drutiny/drutiny:3.0.x-dev
```

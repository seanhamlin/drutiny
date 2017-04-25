# Drutiny (Drupal Scrutiny)

<img src="assets/logo.png" alt="Drutiny logo" align="right"/>

[![Build Status](https://travis-ci.org/seanhamlin/drutiny.svg?branch=master)](https://travis-ci.org/seanhamlin/drutiny) [![Latest Stable Version](https://poser.pugx.org/seanhamlin/drutiny/v/stable)](https://packagist.org/packages/seanhamlin/drutiny) [![Total Downloads](https://poser.pugx.org/seanhamlin/drutiny/downloads)](https://packagist.org/packages/seanhamlin/drutiny) [![Latest Unstable Version](https://poser.pugx.org/seanhamlin/drutiny/v/unstable)](https://packagist.org/packages/seanhamlin/drutiny) [![License](https://poser.pugx.org/seanhamlin/drutiny/license)](https://packagist.org/packages/seanhamlin/drutiny)

This is a generic Drupal site auditing and optional remediation tool.

## Why another site audit tool?

Traditional site audit (e.g. the [checklist API](https://www.drupal.org/project/checklistapi) modules in Drupal rely on having certain modules or code present on the server in order to gather the required metrics. The main issue is that if you fail to even have these modules enabled at all, then no auditing will take place in the first instance. This can be a real issue.

Other extensions (e.g. the [site_audit](https://www.drupal.org/project/site_audit) drush extension) are constrained to running only Drush based checks, and you are limited to only excluding checks, you also cannot customise anything about the checks you do run.

This tool is different, all checks are from the outside looking in, and require no special code or modules to be enabled on the remote site. This means you can audit all environments from development to production and not leave any lasting performance degradation. Your definition of best practice can evolve outside your Drupal sites, and the checks that you run against the site will always be up to date. Druntiny also integrates with other best of breed tools to ensure that you have maximum flexibility when it comes to running checks, e.g.

* Drush (e.g. check the status of a module, or get a variable value)
* SSH (e.g. filesystem checks, directory size checks)
* [Phantomas](https://github.com/macbre/phantomas) (e.g. check the actual rendering of the site and ensure there are no in-page 404s)

If a particular check pertains to just Drupal 7 or Drupal 8 then it will be namespaced as such. In this fashion you are able to run site audits against either Drupal 7 or Drupal 8 sites using the same Drutiny codebase.

## Installation
It is recommended to install Drutiny into your project with [composer](https://getcomposer.org). Drutiny is a require-dev type dependency.

```
composer require --dev drutiny/drutiny 2.x
```

[Drush](http://www.drush.org/en/master/) is also required. Its not specifically marked as a dependency as the version of drush to use will depend on the site you're auditing.

## Usage
Drutiny is a command line tool that can be called from the composer vendor bin directory

```
./vendor/bin/drutiny
```

### Finding checks available to run
Drutiny comes with a `check:list` command that lists all the checks available to you.

```
./vendor/bin/drutiny check:list
```

Checks provided by other packages such as [drutiny/acquia](https://github.com/fiasco/drutiny-acquia) will also appear here if they are installed.

### Running a check
A check can be run against a site by using `check:run` and passing the check name and site target:

```
./vendor/bin/drutiny check:run d8.page.cache drush:@drupalvm.dev
```

The command above would run the `d8.page.cache` check against the drush alias `@drupalvm.dev` which should point to an active site. 

Some checks have parameters you can specify which can be passed in at calltime. Use `check:info` to find out more about the parameters available for a check.

```
./vendor/bin/drutiny check:run -p max_age=600 d8.page.cache drush:@drupalvm.dev
```

Checks are simple self contained classes that are simple to read and understand. Drutiny can be extended very easily to check for your own unique requirements. Pull requests are welcome as well, please see the [contributing guide](./CONTRIBUTING.md).

### Remediation
Some checks have remedative capability. Passing the `--remediate` flag into the call with "auto-heal" the site if the check fails on first pass.

```
./vendor/bin/drutiny check:run -p max_age=600 --remediate d8.page.cache drush:@drupalvm.dev
```

### Running a profile of checks
A site audit is running a collection of checks that make up a profile. This allows you to audit against a specific standard, policy or best practice. Drutiny comes with some base profiles which you can find using `profile:list`. You can run a profile with `profile:run` in a simlar format to `check:run`.

```
./vendor/bin/drutiny profile:run --remediate d8 drush:@drupalvm.dev
```

Parameters can not be passed in at runtime for profiles but are instead predefined by the profile itself.

### Reporting
By default, profile runs report to the console but reports can also be exported in html and json formats.

```
./vendor/bin/drutiny profile:run --remediate --format=html --report-filename=drupalvm-dev.html d8 drush:@drupalvm.dev
```

### Phantomas

If you wish to run browser based checks (e.g. page weight check), then you will require [Phantomas](https://github.com/macbre/phantomas) to be installed on your local system. Note that these checks are optional.

First install Node:

```
brew install node
```

And then use NPM to install phantomas:

```
npm install --global --no-optional phantomas phantomjs-prebuilt@^2.1.5
```


## How to run against sites in a Drupal multisite (TODO)

**This is currently not supported in the 2.x branch**
You first need to create a domains file that lists all domains you want to run an audit against. An example is provided with `domains-example.yml` to which you can copy and make you own version:

```
cp domains{-example,}.yml
```

Example on how to run using a custom profile:

```
./bin/drutiny audit:multisite [ALIAS] --profile=[YOUR_PROFILE] --domain-file=domains.yml
```

You do not have to run the site audit against all sites, you can elect to run it against a subset, or even just one. The domains you place in the YAML file dictate this.


## Getting help

Because this is a Symfony Console application, you have some other familiar commands:

```
./bin/drutiny help profile:run
```

In particular, if you use the `-vvv` argument, then you will see all the drush commands, and SSH commands printed to the screen.


# Credits

* [Theodoros Ploumis](https://github.com/theodorosploumis) for [creating the logo](https://github.com/seanhamlin/drutiny/issues/79) for Drutiny.

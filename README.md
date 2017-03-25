# Drutiny (Drupal Scrutiny)

[![Build Status](https://travis-ci.org/seanhamlin/drutiny.svg?branch=master)](https://travis-ci.org/seanhamlin/drutiny) [![Latest Stable Version](https://poser.pugx.org/seanhamlin/drutiny/v/stable)](https://packagist.org/packages/seanhamlin/drutiny) [![Total Downloads](https://poser.pugx.org/seanhamlin/drutiny/downloads)](https://packagist.org/packages/seanhamlin/drutiny) [![Latest Unstable Version](https://poser.pugx.org/seanhamlin/drutiny/v/unstable)](https://packagist.org/packages/seanhamlin/drutiny) [![License](https://poser.pugx.org/seanhamlin/drutiny/license)](https://packagist.org/packages/seanhamlin/drutiny)

This is a generic Drupal 7 and Drupal 8 site auditing and optional remediation tool.

## Why another site audit tool?

Traditional site audit (e.g. the [checklist API](https://www.drupal.org/project/checklistapi) modules in Drupal rely on having certain modules or code present on the server in order to gather the required metrics. The main issue is that if you fail to even have these modules enabled at all, then no auditing will take place in the first instance. This can be a real issue.

Other extensions (e.g. the [site_audit](https://www.drupal.org/project/site_audit) drush extension) are constrained to running only Drush based checks, and you are limited to only excluding checks, you also cannot customise anything about the checks you do run.

This tool is different, all checks are from the outside looking in, and require no special code or modules to be enabled on the remote site. This means you can audit all environments from development to production and not leave any lasting performance degradation. Your definition of best practice can evolve outside your Drupal sites, and the checks that you run against the site will always be up to date. Druntiny also integrates with other best of breed tools to ensure that you have maximum flexibility when it comes to running checks, e.g.

* Drush (e.g. check the status of a module, or get a variable value)
* SSH (e.g. filesystem checks, directory size checks)
* [Phantomas](https://github.com/macbre/phantomas) (e.g. check the actual rendering of the site and ensure there are no in-page 404s)

If a particular check pertains to just Drupal 7 or Drupal 8 then it will be namespaced as such. In this fashion you are able to run site audits against either Drupal 7 or Drupal 8 sites using the same Drutiny codebase.

## What is a site audit comprised of?

A site audit is comprised of a profile, and a profile can contain 1 or more checks, and those checks can have optional arguments supplied. This means that you can create a profile that is specific to your own internal guidelines, and not some generic report that someone else made that may or may not be of any use to you.

Checks are simple self contained classes that are simple to read and understand. Drutiny can be extended very easily to check for your own unique requirements. Pull requests are welcome as well, please see the [contributing guide](./CONTRIBUTING.md).

## Requirements

**1. Drush installed**

Drush is required to be installed locally and be available on your path. Drush 8 is recommended. Having a remote-host attribute in the drush alias file is required if you want to run any of the SSH checks.

**2. A Drush alias for the site**

For every site you want to run the report against, you require a complete drush alias:

```
<?php
$aliases['www.example.com'] = array(
  'uri' => 'www.example.com',
  'root' => '/var/www/html/docroot',
  'remote-host' => 'server.example.com',
  'remote-user' => 'example',
  'ssh-options' => '-F /dev/null',
  'path-aliases' => array(
    '%drush-script' => 'drush6',
    '%dump-dir' => '/mnt/tmp/',
  )
);
```

**3. Composer**

Needed to install Symfony Console and other PHP libraries.

```
composer install
```

**4. Phantomas**

If you wish to run browser based checks (e.g. page weight check), then you will require [Phantomas](https://github.com/macbre/phantomas) to be installed on your local system. Note that these checks are optional.

First install Node:

```
brew install node
```

And then use NPM to install phantomas:

```
npm install --global --no-optional phantomas phantomjs-prebuilt@^2.1.5
```


## How to run against a single Drupal site

Run using the `default` profile (replace [alias] with your drush alias):

```
./bin/drutiny audit:site [alias]
```

Run a side audit using the `govcms_saas` profile:

```
./bin/drutiny audit:site [alias] --profile=govcms_saas
```

Because this is Symfony console, you have some other familiar commands:

```
./bin/drutiny help audit:site
```

In particular, if you use the `-v` argument, then you will see all the drush commands, and SSH commands printed to the screen.


## How to run against an entire Acquia Cloud Site Factory

This will lookup a list of all Site Factory sites currently running, and will loop around them all. This is much like the multisite audit, except there is no need to supply a list of domains.

Run using the `default` profile (replace [alias] with your drush alias):

```
./bin/drutiny audit:acsf [alias]
```

Run a side audit using the `govcms_saas` profile:

```
./bin/drutiny audit:acsf [alias] --profile=govcms_saas
```

You can also write the output to a file:

```
./bin/drutiny audit:acsf [alias] --profile=govcms_saas --report-dir=/tmp
```


## How to run against sites in a Drupal multisite

You first need to create a domains file that lists all domains you want to run an audit against. An example is provided with `domains-example.yml` to which you can copy and make you own version:

```
cp domains{-example,}.yml
```

Run using the `govcms_saas` profile (replace [alias] with your drush alias):

```
./bin/drutiny audit:multisite [alias] --profile=govcms_saas --report-dir=/tmp --domain-file=domains.yml
```

You do not have to run the site audit against all sites, you can elect to run it against a subset, or even just one.


## Bash aliases

This could be helpful if you want to be able to run the command from anywhere:

```
alias as='/path/to/drutiny audit:site'
alias aa='/path/to/drutiny audit:acsf'
alias am='/path/to/drutiny audit:multisite'
```


## Report formats and locations

Report formats be controlled with the `--format` option, and you can chain them together to get the same report in multiple formats. For example:

```
./bin/drutiny audit:site [alias] --format=html --format=json
```

Reports by default will appear in the `reports` directory, but can be altered with another argument

```
./bin/drutiny audit:site [alias] --format=html --format=json --report-dir=/tmp
```


## Auto remediation

Certain checks have an auto-remediation feature, in order to use this you will need to pass in `--auto-remediate` as a parameter on the command line. In general auto-remediation is only ever added into checks where the remediation is unlikely to break the site, e.g. it will never disable modules (as this could break the site) but it will set certain variables.

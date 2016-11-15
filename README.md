# Site audit

This is a generic Drupal 7 site auditing tool.

## Why another site audit tool?

This tool is different, in the sense that a site audit is comprised of a profile, and a profile can contain 1 or more checks, and those checks can have optional arguments supplied. This means that you can create a profile that is specific to your own internal guidelines, and not some generic report that may or may not be of any use to you.

Checks are simple classes, that at the moment can either be Drush based checks, or SSH based checks. This allows for easy extension to cover any needs.

## Requirements

**Drush installed**

Drush is required to be installed locally and be available on your path. Drush 8 is recommended. Having a remote-host attribute in the drush alias file is required if you want to run any of the SSH checks.

**A Drush alias for the site**

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

**Composer**

Needed to install Symfony Console and related libraries.

```
composer install
```

**Phantomas**

If you wish to run browser based checks (e.g. page weight check), then you will require [Phantomas](https://github.com/macbre/phantomas) to be installed on your local system. Note that these checks are optional.

First install Node:

```
brew install node
```

And then use NPM to install phantomas:

```
npm install --global --no-optional phantomas phantomjs-prebuilt@^2.1.5
```

## How to run against a single site

Run using the `default` profile (replace [alias] with your drush alias):

```
php bin/site-audit audit:site [alias]
```

Run a side audit using the `govcms_saas` profile:

```
php bin/site-audit audit:site [alias] --profile=govcms_saas
```

Because this is Symfony console, you have some other familiar commands:

```
php bin/site-audit help audit:site
```

In particular, if you use the `-v` argument, then you will see all the drush commands, and SSH commands printed to the screen.

You can also write the output to a file:

```
php bin/site-audit audit:site [alias] --profile=govcms_saas --report-dir=/tmp
```

## How to run against an entire Site Factory

Run using the `default` profile (replace [alias] with your drush alias):

```
php bin/site-audit audit:acsf [alias]
```

Run a side audit using the `govcms_saas` profile:

```
php bin/site-audit audit:acsf [alias] --profile=govcms_saas
```

You can also write the output to a file:

```
php bin/site-audit audit:acsf [alias] --profile=govcms_saas --report-dir=/tmp
```


## How to run against sites in a multisite

You first need to create a domains file that lists all domains you want to run an audit against. An example is provided with `domains-example.yml` to which you can copy and make you own version:

```
cp domains{-example,}.yml
```

Run using the `govcms_saas` profile (replace [alias] with your drush alias):

```
php bin/site-audit audit:multisite [alias] --profile=govcms_saas --report-dir=/tmp --domain-file=domains.yml
```

## Bash aliases

This could be helpful if you want to be able to run the command from anywhere:

```
alias as='php /path/to/site-audit audit:site'
alias aa='php /path/to/site-audit audit:acsf'
alias am='php /path/to/site-audit audit:multisite'
```

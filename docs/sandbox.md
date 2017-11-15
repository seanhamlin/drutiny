# Sandbox
The Sandbox is a runtime object that execute policies against an audit. It
contains access to environmental tools needed to audit and remediate sites
against the policies executed.

## exec
`exec` is the function to give you access to the target's OS command line.
Use this function to run any underlying shell commands.

```php
<?php

$output = $sandbox->exec('ls -l');
```

## localExec
Test targets can be remote (e.g. Drush can specify access to remove Drupal
sites). In these instances `exec` will execute on the remote server. If the
command you're executing should always run on the same environment that Drutiny
is running from then you should instead use `localExec`.

```php
<?php
$date = $sandbox->localExec('date +%Y-%m-%d %H:%i');
```

## drush
Drush commands can be run against Drupal targets using the Sandbox::drush() method.
Where drush commands are normally separated by hyphens, Drutiny supports camel
case naming.

```php
<?php

$list = $sandbox->drush(['format' => 'json'])->pmList();
```

Where supported, if json format is requested as illustrated above, drutiny will parse the response and return the output in PHP.

## getParameter
Parameters that are set in the command line, profile or by the policy can be
accessed using Sandbox::getParameter. Audits may specify default values for
parameters if they choose.

```php
<?php

$name = $sandbox->getParameter('module_name', 'shield');
```

## setParameter
Parameters to be used in the rendered messages of the policy can be set using
Sandbox::setParameter.

Parameters are rendered through [mustache](https://mustache.github.io/) templating

```php
<?php

$sandbox->setParameter('module_status', 'enabled');
```

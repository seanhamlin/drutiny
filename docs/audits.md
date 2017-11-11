# Writing Audits for Drutiny

A Drutiny `audit` is a PHP class that executes a `policy` defined in YAML.
Audit classes do the heavy lifting for a policy and are ideally abstract enough
to support multiple policies. Such an example is the [Module Enabled](https://github.com/drutiny/drutiny/blob/2.x/src/Audit/Drupal/ModuleEnabled.php)
class which checks if a module is enabled. However, the module it checks is set
by the policy. In this way, may policies can be created using the same audit
class.

## Getting Started
An audit class should live under an `Audit` folder in a PSR-4 directory structure
and extend from `Drutiny\Audit`.

```php
<?php
namespace Path\to\Audit;
use Drutiny\Audit;

/**
 * Generic module is disabled check.
 */
class FooAssesor extends Audit {

```

An `Audit` class requires a single method also called `audit`. Its passed in a `Drutiny\Sandbox\Sandbox` object which holds all tools available to running an audit.

```php
<?php

use Drutiny\Sandbox\Sandbox;

// ...

public function audit(Sandbox $sandbox)
{

}
```

## The Sandbox
The Sandbox object passed to the check method contains access to drivers you may use in your audit such as `drush`.

```php
<?php
/**
 * @inheritDoc
 */
public function audit(Sandbox $sandbox) {
  // Use drush to confirm Drupal settings deny llamas.
  $config = $sandbox->drush(['format' => 'json'])
                    ->configGet('llamas.settings', 'allowed');
  $denied = $config['llamas.settings:allowed'] == FALSE;

  // Confirm llamas have not accessed the site.
  $lama_access = $sandbox->exec('grep llamas /var/log/apache/access.log | grep -v 403');

  return $denied && empty($lama_access);
}
```

## Remediation
Remediation is an optional capability your `Audit` can support.
To do so, it must implement `Drutiny\RemediableInterface`.

When policies and profiles are run, they can optionally opt into to auto-remediation which will call the `remediation` method if the audit method returns FALSE.

```php
<?php

namespace Path\to\Audit;
use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;

/**
 * Generic module is disabled check.
 */
class FooAssesor extends Audit implements RemediableInterface {

// ...

/**
 * @inheritDoc
 */
public function remediate(Sandbox $sandbox) {
  // This calls: drush config-set -y llamas.settings allowed 0
  $sandbox->drush()->configSet('-y', 'llamas.settings', 'allowed', 0);

  // Re-check now the config should have changed.
  return $this->check($sandbox);
}
```

## Parameters
Parameters allow you to configure the audit based on the runtime environment.
For example, the page cache audit contains parameters to allow you to audit what the page cache `max_age` setting should be.

Parameters are defined in policies and can be used in the `audit` and `remediate` methods.

```yaml
# Parameters mentioned in bar.policy.yml
parameters:
  foo:
    type: string
    description: "A measure of foo to apply to denied llamas"
    default: bar
```

```php

<?php
/**
 * @inheritDoc
 */
public function audit(Sandbox $sandbox) {
  $foo = $sandbox->getParameter('foo');

  $config = $sandbox->drush(['format' => 'json'])
                    ->configGet('llamas.settings', 'foo');
  $this->setParameter('actual_foo', $config['llamas.settings:foo']);
  return $foo == $config['llamas.settings:foo'];
}
```

You can use `$sandbox->setParameter()` to set parameters that maybe used to render the results of an audit or remediation.

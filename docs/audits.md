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

  // Return TRUE/FALSE is the same as Audit::SUCCESS or Audit::FAILURE
  return $denied && empty($lama_access);
}
```


## Return values
The audit expects a returned value to indicate the outcome of the audit. The follow table describes the return options and their meaning.

Return Value                    | Purpose
------------------------------- | ----------------------------------------------------
`Drutiny\Audit::SUCCESS`        | The policy successfully passed the audit.
`Drutiny\Audit::PASS`           | Same as `Audit::SUCCESS`
`Drutiny\Audit::FAILURE`        | The policy failed to pass the audit.
`Drutiny\Audit::FAIL`           | Same as `Audit::FAILURE`
`Drutiny\Audit::NOTICE`         | An audit returned non-assertive information.
`Drutiny\Audit::WARNING`        | An audit returned **successful** but with a warning.
`Drutiny\Audit::WARNING_FAIL`   | An audit returned a **failure** but with a warning.
`Drutiny\Audit::ERROR`          | An audit did not complete and returned an error.
`Drutiny\Audit::NOT_APPLICABLE` | An audit was not applicable to the target.

In addition to using Return Values, audits can also return `TRUE`, `FALSE` and
`NULL` values which correlate to `Drutiny\Audit::SUCCESS`, `Drutiny\Audit::FAILURE`
and `Drutiny\Audit::NOT_APPLICABLE` respectively.

## Audit Prerequisites
In order to keep audit methods as lean as possible and to focus on evaluating the
success/failure of the task at hand, Drutiny Audit classess support the ability
to validate prerequisites before attempting an audit. These are called **require**
methods.

Any protected method on an Audit class that starts with the term "require" will
be considered a prerequisite to pass prior to an audit being run.

```php
<?php

namespace Path\to\Audit;
use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Target\DrushTarget;

class DrushVersionCheck extends Audit {

  /**
   * This will be called before audit().
   *
   * Must return TRUE to continue audit.
   */
  protected function requireDrushTarget(Sandbox $sandbox)
  {
    return $sandbox->getTarget() instanceof DrushTarget;
  }

  public function audit(Sandbox $sandbox) {

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
  return $this->audit($sandbox);
}
```

## Parameters
Parameters allow you to configure the audit based on the runtime environment.
For example, the page cache audit contains parameters to allow you to audit what the page cache `max_age` setting should be.

Parameters are defined as annotations in the audit class. This allows policy writers
to understand how to use the parameters when integrating with a specific audit.

```php
<?php

namespace Path\to\Audit;
use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;
use Drutiny\Annotation\Param;

/**
 * Generic module is disabled check.
 * @Param(
 *  name = "foo",
 *  type = "string",
 *  description = "A measure of foo to apply to denied llamas",
 *  default = "baz",
 * )
 */
class FooAssesor extends Audit implements RemediableInterface {
```

Parameters can be overwritten in policies:

```yaml
# Parameters mentioned in bar.policy.yml
class: \Drutiny\path\to\FooAssesor
parameters:
  foo:
    type: string
    description: "A measure of foo to apply to denied llamas"
    default: bar
```

Parameters are then used inside the audit and remediation methods to allow elements
of the audit to be more configurable and extensible to other implementations:

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

  // Return TRUE/FALSE is the same as Audit::SUCCESS or Audit::FAILURE
  return $foo == $config['llamas.settings:foo'];
}
```

You can use `$sandbox->setParameter()` to set parameters that maybe used to render the results of an audit or remediation.

## Tokens
Tokens are parameters that are available to render in the output messaging of the policy. E.g. success, failure, warning and description messages. All parameters are implicitly tokens, but in addition, an audit may set parameters not used for the purposes of the audit but purely for policy messaging.

```php
<?php

namespace Path\to\Audit;
use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\RemediableInterface;
use Drutiny\Annotation\Param;
use Drutiny\Annotation\Token;

/**
 * Generic module is disabled check.
 * @Param(
 *  name = "foo",
 *  type = "string",
 *  description = "A measure of foo to apply to denied llamas",
 *  default = "baz",
 * )
 * @Token(
 *  name = "actual_foo",
 *  type = "string",
 *  description = "Config setting for llamas.settings:foo"
 * )
 */
class FooAssesor extends Audit implements RemediableInterface {
/**
 * @inheritDoc
 */
public function audit(Sandbox $sandbox) {
  $foo = $sandbox->getParameter('foo');

  $config = $sandbox->drush(['format' => 'json'])
                    ->configGet('llamas.settings', 'foo');
  $this->setParameter('actual_foo', $config['llamas.settings:foo']);

  // Return TRUE/FALSE is the same as Audit::SUCCESS or Audit::FAILURE
  return $foo == $config['llamas.settings:foo'];
}
```

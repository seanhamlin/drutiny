# Writting Checks for Drutiny

Drutiny is extensible. If it does't support the type of check you want to run
you can simply add one. Drutiny is an "outside-in" style site auditor which
means you're not constrained to a single runtime environment such drush, Drupal
Console, PHPUnit or Behat. All of these and more can be used with Drutiny to
construct a comprehensive audit of your site.

## Getting Started
A Drutiny check consists of two files.

* A `YAML` file that informs Drutiny of the check and provides to variables and
content for the check
* A PHP class the executes the check and remediation if applicable.

Drutiny comes with a scaffolding command to pre-generate these files for you:

```bash
$ ./bin/drutiny check:generate
What is the title of your check? No lamas allowed
Please provide a machine name for your check? lamas.deny
Does this check support auto-remediation? (y/n) y
Created src/Check/lamas.deny.yml
Created src/Check/NoLamasAllowed.php
```

`lamas.deny` will now be listed in `./bin/drutiny check:list` and you can run it
using `./bin/drutiny check:run lamas.deny <target>`.

If you move your PHP file to a more appropriate sub-location, be sure to update
its namespace in both the PHP file and in the yaml file.

## Writing the Check
Checks are done by calling the `check` method within the check's PHP class. In
the example above, this PHP class can be located at `src/Check/NoLamasAllowed.php`.

The check method should return `TRUE` if the check passes successfully or `FALSE`
if the check fails. If the check encounters a failure or exception which does not
allow the check to determine if the site state is correct, then an `Exception`
of any type can be thrown.

```php
/**
 * @inheritDoc
 */
public function check(Sandbox $sandbox) {
  throw new \Exception("How does one ensure lamas are denied?");
}
```

## The Sandbox
The Sandbox object passed to the check method contains access to drivers you
may use in your check such as `drush`.

```php
/**
 * @inheritDoc
 */
public function check(Sandbox $sandbox) {
  // Use drush to confirm Drupal settings deny lamas.
  $config = $sandbox->drush(['format' => 'json'])
                    ->configGet('lamas.settings', 'allowed');
  $denied = $config['lamas.settings:allowed'] == FALSE;

  // Confirm lamas have not accessed the site.
  $lama_access = $sandbox->exec('grep lamas /var/log/apache/access.log | grep -v 403');

  return $denied && empty($lama_access);
}
```

## Remediation
Remediation is both an optional choice to execute when running a check or profile
but it is also optional for the check to implement. Remediable checks implement
`Drutiny\Check\RemediableInterface`.

If a check fails and remediation is enabled, then the `remediate` method is
called which attempts the remediation and then returns `TRUE` or `FALSE` as to if
the remediation was successful or not. In most cases, recalling the `check`
method from within the remediation is the best approach.

```php
/**
 * @inheritDoc
 */
public function remediate(Sandbox $sandbox) {
  // This calls: drush config-set -y lamas.settings allowed 0
  $sandbox->drush()->configSet('-y', 'lamas.settings', 'allowed', 0);

  // Re-check now the config should have changed.
  return $this->check($sandbox);
}
```

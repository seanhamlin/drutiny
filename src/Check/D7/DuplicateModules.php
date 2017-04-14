<?php

namespace Drutiny\Check\D7;

use Drutiny\Check\Check;
use Drutiny\Sandbox\Sandbox;
use Symfony\Component\Yaml\Yaml;

/**
 * Duplicate modules.
 */
class DuplicateModules extends Check {

  /**
   * @inheritdoc
   */
  public function check(Sandbox $sandbox) {
    $config = $sandbox->drush(['format' => 'json'])
      ->status();

    $docroot = $config['root'];

    $command = <<<CMD
find $docroot -name '*.module' -type f |\
grep -Ev 'drupal_system_listing_(in)?compatible_test' |\
grep -oe '[^/]*\.module' | cut -d'.' -f1 | sort |\
uniq -c | sort -nr | awk '{print $2": "$1}'
CMD;

    $output = $sandbox->exec($command);

    $module_count = array_filter(Yaml::parse($output), function ($count) {
      return $count > 1;
    });

    $sandbox->setParameter('duplicate_modules', array_keys($module_count));

    return count($module_count) == 0;
  }

}

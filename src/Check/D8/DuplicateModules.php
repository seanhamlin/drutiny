<?php

namespace Drutiny\Check\D8;

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
find $docroot -name '*.info.yml' -type f |\
grep -Ev '/themes/|/test' |\
grep -oe '[^/]*\.info.yml' | cut -d'.' -f1 | sort |\
uniq -c | sort -nr | awk '{print $2": "$1}'
CMD;

    $output = $sandbox->exec($command);

    // Ignore modules where there are only 1 of them.
    $module_count = array_filter(Yaml::parse($output), function ($count) {
      return $count > 1;
    });

    $sandbox->setParameter('duplicate_modules', array_keys($module_count));

    return count($module_count) == 0;
  }

}

<?php

namespace Drutiny;

use Symfony\Component\Console\Input\InputInterface;
use Drutiny\DomainList\DomainListRegistry;
use Drutiny\Target\Registry as TargetRegistry;

class DomainSource {

  public static function loadFromInput(InputInterface $input)
  {
    if (!$source = $input->getOption('domain-source')) {
      return FALSE;
    }

    $options = [];
    foreach ($input->getOptions() as $name => $value) {
      if (strpos($name, 'domain-source-' . $source) === FALSE) {
        continue;
      }
      $options[str_replace('domain-source-' . $source . '-', '', $name)] = $value;
    }
    $domain_loader = DomainListRegistry::loadFromInput($source, $options);

    $target = TargetRegistry::loadTarget($input->getArgument('target'));

    $allowlist = $input->getOption('domain-source-allowlist');
    $denylist = $input->getOption('domain-source-denylist');

    // Filter domains by allowlist and denylist.
    $filter = function ($domain) use ($allowlist, $denylist) {
      // Whitelist priority.
      if (!empty($allowlist)) {
        foreach ($allowlist as $regex) {
          if (preg_match("/$regex/", $domain)) {
            return TRUE;
          }
        }
        // Did not pass the whitelist.
        return FALSE;
      }
      if (!empty($denylist)) {
        foreach ($denylist as $regex) {
          if (preg_match("/$regex/", $domain)) {
            return FALSE;
          }
        }
      }
      return TRUE;
    };

    $domains = $domain_loader->getDomains($target, $filter);

    // Filter the domains a second time incase the domain loader didn't use
    // the filter.
    return array_filter($domains, $filter);
  }
}

 ?>

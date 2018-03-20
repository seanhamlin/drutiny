<?php

namespace Drutiny\Docs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Helper for building checks.
 */
class BuildDocsCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('docs:build')
      ->setDescription('Build docs from source code annotations.');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $policies = (new \Drutiny\Registry())->policies();

    file_exists('docs/index.md') || copy('README.md', 'docs/index.md');

    if (file_exists('docs/policies')) {
      exec('rm -rf docs/policies');
    }

    mkdir('docs/policies');

    $toc = [];
    $pages = [];

    foreach ($policies as $policy) {
      $package = $this->findPackage($policy);



      $md = [];
      $md[] = $link = strtr('## title (name)', [
        'title' => $policy->get('title'),
        'name' => $policy->get('name'),
      ]);

      $toc[$policy->get('title')] = [
        'title' => $policy->get('title'),
        'name' => $policy->get('name'),
        'package' => $package,
        'link' => str_replace(' ', '-', trim(preg_replace('/[^a-z0-9 \-]/', '', strtolower($link)))),
      ];

      $md[] = "**Package**: `$package`  ";
      $md[] = "**Class**: `" . $policy->get('class') . "`";
      $md[] = '';
      $md[] = $policy->get('description');

      $params = $policy->get('parameters');
      if (!empty($params)) {
        $md[] = '';
        $md[] = '### Parameters';
        $md[] = 'Name | Type | Description | Default';
        $md[] = '-- | -- | -- | --';

        foreach ($params as $name => $param) {
          $md[] = strtr('Name | Type | Description | Default', [
            'Name' => $name,
            'Type' => $param['type'],
            'Description' => $param['description'],
            'Default' => $param['default'],
          ]);
        }
      }

      $tokens = $policy->get('tokens');
      if (!empty($tokens)) {
        $md[] = '';
        $md[] = '### Parameters';
        $md[] = 'Name | Type | Description | Default';
        $md[] = '-- | -- | -- | --';

        foreach ($tokens as $name => $token) {
          $md[] = strtr('Name | Type | Description | Default', [
            'Name' => $name,
            'Type' => $token['type'],
            'Description' => $token['description'],
            'Default' => $token['default'],
          ]);
        }
      }

      $pages[$package][$policy->get('name')] = implode(PHP_EOL, $md);
    }

    $nav = [['Overview' => 'policy-library.md']];
    foreach ($pages as $package => $list) {
      ksort($list);

      $filepath = 'policies/' . str_replace('/', '-', $package) . '.md';
      file_put_contents("docs/$filepath", implode("\n\n", $list));
      $output->writeln("Written docs/$filepath.");
      $nav[] = [$package => $filepath];
    }

    $md = ['# Policy Library'];
    $md[] = '';
    $md[] = 'Title | Name | Package';
    $md[] = '-- | -- | --';
    ksort($toc);
    foreach ($toc as $item) {
      $filepath = 'policies/' . str_replace('/', '-', $item['package']) . '.md';
      $md[] = "[{$item['title']}]($filepath#{$item['link']}) | {$item['name']} | [{$item['package']}](https://github.com/{$item['package']})";
    }
    file_put_contents('docs/policy-library.md', implode(PHP_EOL, $md));

    $mkdocs = Yaml::parse(file_get_contents('mkdocs.yml'));
    $mkdocs['pages'][3] = ['Policy Library' => $nav];
    file_put_contents('mkdocs.yml', Yaml::dump($mkdocs, 6));
    $output->writeln("Updated mkdocs.yml");
  }

  protected function findPackage($policy)
  {
      $filepath = $policy->get('filepath');
      $composer = FALSE;
      while ($filepath) {
        $filepath = dirname($filepath);

        if (file_exists($filepath . '/composer.json')) {
          break;
        }
      }

      $json = file_get_contents($filepath . '/composer.json');
      $composer = json_decode($json, TRUE);
      return $composer['name'];
  }

}

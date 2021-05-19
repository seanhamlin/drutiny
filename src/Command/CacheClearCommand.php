<?php

namespace Drutiny\Command;

use Drutiny\Container;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Cache\Adapter\FilesystemAdapter as Cache;

/**
 *
 */
class CacheClearCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('cache:clear')
      ->setAliases(['cc'])
      ->setDescription('Clear the Drutiny cache');
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    foreach (array_keys(Container::config('Cache')) as $bin) {
      $cache = Container::cache($bin);
      $cache->clear();
    }

    $io = new SymfonyStyle($input, $output);
    $io->success('Cache is cleared.');
  }
}

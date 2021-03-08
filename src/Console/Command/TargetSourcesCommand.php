<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Drutiny\Target\TargetInterface;
use Drutiny\Target\TargetSourceInterface;

/**
 *
 */
class TargetSourcesCommand extends DrutinyBaseCommand
{

  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('target:sources')
        ->setDescription('List the different types of target sources.');
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $types = array_filter($this->getContainer()->getServiceIds(), function ($id) {
          if (strpos($id, 'target.') !== 0) {
            return false;
          }
          $target = $this->getContainer()->get($id);
          return $target instanceof TargetInterface;
        });

        $rows = [];
        foreach ($types as $type) {
          $instance = $this->getContainer()->get($type);
          list($i, $tag) = explode('.', $type, 2);
          $rows[] = [$tag, get_class($instance), $instance instanceof TargetSourceInterface ? 'yes' : 'no'];
        }

        $io = new SymfonyStyle($input, $output);
        $io->table(['source', 'class', 'listable'], $rows);

        return 0;
    }
}

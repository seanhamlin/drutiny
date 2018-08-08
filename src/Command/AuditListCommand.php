<?php

namespace Drutiny\Command;

use Drutiny\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 *
 */
class AuditListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('audit:list')
      ->setDescription('Show all php audit classes available.')
      ;
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $finder = new Finder();
    $finder->directories()
      ->in('.')
      ->exclude('./deploy')
      ->name('Audit');

    $files = new Finder();
    $files->files()->name('*.php');
    foreach ($finder as $dir) {
      if (strpos($dir->getRealPath(), '/tests/') !== FALSE) {
        continue;
      }
      $files->in($dir->getRealPath());
    }

    $list = [];
    foreach ($files as $file) {
      include_once $file->getRealPath();
    }

    $audits = array_filter(get_declared_classes(), function ($class) {
      $reflect = new \ReflectionClass($class);
      if ($reflect->isAbstract()) {
        return FALSE;
      }
      return $reflect->isSubclassOf('\Drutiny\Audit');
    });

    sort($audits);

    $io = new SymfonyStyle($input, $output);
    $io->title('Drutiny Audit Classes');
    $io->listing($audits);
  }

  /**
   *
   */
  protected function formatDescription($text) {
    $lines = explode(PHP_EOL, $text);
    $text = implode(' ', $lines);
    return wordwrap($text, 50);
  }

}

<?php

namespace Drutiny\Console\Helper;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\FormatterInterface;
use Monolog\Logger;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;


/**
 * Pipes log into progress bar.
 */
Class MonologProgressBarHandler extends AbstractProcessingHandler {

  protected ProgressBar $progressBar;
  protected Terminal $terminal;
  protected OutputInterface $output;

  public function __construct(ProgressBar $progressBar, OutputInterface $output, Terminal $terminal, $level = Logger::NOTICE, bool $bubble = true)
  {
      parent::__construct($level, $bubble);
      $this->progressBar = $progressBar;
      $this->terminal = $terminal;
      $this->output = ($output instanceof ConsoleOutputInterface) ? $output->getErrorOutput() : $output;
  }

  /**
   * {@inheritDoc}
   */
  protected function getDefaultFormatter(): FormatterInterface
  {
      return new LineFormatter('%message%');
  }

  /**
   * {@inheritdoc}
   */
  protected function write(array $record): void
  {
      $message = substr($record['formatted'], 0, min($this->terminal->getWidth(), strlen($record['formatted'])));

      if ($record['level'] >=  Logger::ERROR) {
        $this->progressBar->clear();
        $this->output->writeln(sprintf('<error>%s</error>', $message));
        $this->output->writeln('');
      }
      else {
        $this->progressBar->setMessage($message);
      }
  }
}


 ?>

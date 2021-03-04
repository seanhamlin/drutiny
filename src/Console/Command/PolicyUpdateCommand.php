<?php

namespace Drutiny\Console\Command;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class PolicyUpdateCommand extends DrutinyBaseCommand
{
    use LanguageCommandTrait;
  /**
   * @inheritdoc
   */
    protected function configure()
    {
        $this
        ->setName('policy:update')
        ->setDescription('Updates all policies from their respective policy sources.')
        ->addOption(
            'source',
            's',
            InputOption::VALUE_OPTIONAL,
            'Update a specific policy source only.'
        );
        $this->configureLanguage();
    }

  /**
   * @inheritdoc
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $progress = $this->getProgressBar();
        $logger = $this->getLogger();


        $this->initLanguage($input);

        if ($source = $input->getOption('source')) {
          $sources = [$this->getPolicyFactory()->getSource($source)];
        }
        else {
          $sources = $this->getPolicyFactory()->getSources();
        }

        $progress->start(array_sum(array_map(function ($source) {
          return count($source->getList($this->getLanguageManager()));
        }, $sources)));

        foreach ($sources as $source) {
            $logger->notice("Updating " . $source->getName());

            foreach ($source->refresh() as $policy) {
              $progress->advance();
              $logger->notice($source->getName() . ': Updated "' . $policy->title . '"');
            }
        }

        $progress->finish();

        return 0;
    }
}

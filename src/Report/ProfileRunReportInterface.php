<?php

namespace Drutiny\Report;

use Drutiny\ProfileInformation;
use Drutiny\Target\Target;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
interface ProfileRunReportInterface {

  /**
   * @param ProfileInformation $info
   * @param Target $target
   * @param array $result_set
   */
  public function __construct(ProfileInformation $info, Target $target, array $result_set);

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return
   */
  public function render(InputInterface $input, OutputInterface $output);

}

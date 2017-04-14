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
   *
   */
  public function __construct(ProfileInformation $info, Target $target, array $result_set);

  /**
   *
   */
  public function render(InputInterface $input, OutputInterface $output);

}

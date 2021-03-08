<?php

namespace Drutiny\Console\Command;

use Drutiny\Report\FormatInterface;
use Drutiny\Report\FilesystemFormatInterface;
use Drutiny\Profile;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

/**
 *
 */
trait ReportingCommandTrait
{
    protected \DateTime $reportingPeriodStart;
    protected \DateTime $reportingPeriodEnd;
  /**
   * @inheritdoc
   */
    protected function configureReporting()
    {
        $this
        ->addOption(
            'format',
            'f',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Specify which output format to render the report (terminal, html, json). Defaults to terminal.',
            ['terminal']
        )
        ->addOption(
            'report-dir',
            'o',
            InputOption::VALUE_OPTIONAL,
            'For file based formats, use this option to write report to a file directory. Drutiny will automate a filepath if the option is omitted',
            getenv('PWD')
        )
        ->addOption(
            'reporting-period-start',
            null,
            InputOption::VALUE_OPTIONAL,
            'The starting point in time to report from. Can be absolute or relative. Defaults to 24 hours before the current hour.',
            date('Y-m-d H:00:00', strtotime('-24 hours'))
        )
        ->addOption(
            'reporting-period-end',
            null,
            InputOption::VALUE_OPTIONAL,
            'The end point in time to report to. Can be absolute or relative. Defaults to the current hour.',
            date('Y-m-d H:00:00')
        )
        ->addOption(
            'reporting-period',
            null,
            InputOption::VALUE_OPTIONAL,
            'A time range expressed using syntax similar to Sumologic or SignalFx. E.g. 09/02/2021 17:39:30 to 09/02/2021 18:39:30'
        );
    }

    /**
     * Determine a default filepath.
     */
      protected function getReportNamespace(InputInterface $input, $uri = ''):string
      {
          return strtr('target-profile-uri-date', [
            'uri' => $uri,
            'target' => preg_replace('/[^a-z0-9]/', '', strtolower($input->getArgument('target'))),
            'profile' => $input->hasArgument('profile') ? $input->getArgument('profile') : '',
            'date' => date('Ymd-His'),
          ]);
      }

      protected function getFormats(InputInterface $input, Profile $profile = null):array
      {
        foreach ($input->getOption('format') as $format_option) {
          $formats[$format_option] = $this->getContainer()
            ->get('format.factory')
            ->create($format_option, $profile->format[$format_option] ?? []);

          if ($formats[$format_option] instanceof FilesystemFormatInterface) {
            $formats[$format_option]->setWriteableDirectory($input->getOption('report-dir'));
          }
        }
        return $formats;
      }

      /**
       * Get the reporting period start DateTime.
       */
      protected function getReportingPeriodStart(InputInterface $input): \DateTime
      {
        if (isset($this->reportingPeriodStart)) {
          return $this->reportingPeriodStart;
        }
        if ($this->buildReportingPeriod($input)) {
          return $this->reportingPeriodStart;
        }
        $this->reportingPeriodStart = new \DateTime($input->getOption('reporting-period-start'));
        return $this->reportingPeriodStart;
      }

      /**
       * Get the reporting period end DateTime
       */
      protected function getReportingPeriodEnd(InputInterface $input): \DateTime
      {
        if (isset($this->reportingPeriodEnd)) {
          return $this->reportingPeriodEnd;
        }
        if ($this->buildReportingPeriod($input)) {
          return $this->reportingPeriodEnd;
        }
        $this->reportingPeriodEnd = new \DateTime($input->getOption('reporting-period-end'));
        return $this->reportingPeriodEnd;
      }

      /**
       * Attempt to build the reporting period time.
       */
      protected function buildReportingPeriod(InputInterface $input): bool
      {
         if (!$range = $input->getOption('reporting-period')) {
           return false;
         }
         $range = strtolower($range);
         // Parse out format like: 09/02/2021 17:39:30 to 09/02/2021 18:39:30
         if (!preg_match('/([0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2}) to ([0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2})/', $range, $matches)) {
          throw new \InvalidArgumentException("Invalid range given: $range.");
         }

         list($date, $time) = explode(' ', $matches[1]);
         list($day, $month, $year) = explode('/', $date);
         $datetime = "$year-$month-$day $time";
         $this->reportingPeriodStart = new \DateTime($datetime);

         list($date, $time) = explode(' ', $matches[2]);
         list($day, $month, $year) = explode('/', $date);
         $datetime = "$year-$month-$day $time";
         $this->reportingPeriodEnd = new \DateTime($datetime);
         return true;
      }
}

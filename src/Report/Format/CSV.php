<?php

namespace Drutiny\Report\Format;

use Drutiny\AssessmentInterface;
use Drutiny\AuditResponse\AuditResponse;
use Drutiny\Policy;
use Drutiny\Profile;
use Drutiny\Report\Format;
use Drutiny\Report\FormatInterface;
use Drutiny\Report\FilesystemFormatInterface;
use League\Csv\RFC4180Field;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CSV extends Format implements FilesystemFormatInterface
{
    protected string $name = 'csv';
    protected string $extension = 'csv';
    protected array $datasets;
    protected string $directory;

    /**
     * {@inheritdoc}
     */
    public function setWriteableDirectory(string $dir):void
    {
      $this->directory = $dir;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension():string
    {
      return $this->extension;
    }

    public function render(Profile $profile, AssessmentInterface $assessment):FormatInterface
    {

        $uuid = $this->uuid();
        $date = date('c', REQUEST_TIME);
        $schemas = [];
        $insert = [];

        $target = $this->container->get('target');
        $target_class = str_replace('\\', '', get_class($target));

        $insert['Drutiny_Target_'.$target_class.'_Data'][0] = [
          'assessment_uuid' => $uuid,
          'target' => $target->getId(),
          'date' => $date,
        ];

        foreach ($target->getPropertyList() as $property_name) {
          $data = $target[$property_name];
          // Can't store objects.
          if (is_object($data)) {
            continue;
          }
          if (is_array($data) && isset($data['field_derived_key_salt'])) {
            unset($data['field_derived_key_salt']);
          }
          $insert['Drutiny_Target_'.$target_class.'_Data'][0][$property_name] = json_encode($data);
        }

        $defaults = [
          'assessment_uuid' => $uuid,
          'profile' => $profile->name,
          'target' => $target->getId(),
          'start' => $profile->getReportingPeriodStart()->format('c'),
          'end' => $profile->getReportingPeriodEnd()->format('c'),
          'policy_name' => NULL,
          'policy_title' => NULL,
          'language' => NULL,
          'type' => NULL,
          'result_type' => NULL,
          'result_severity' => NULL,
          'date' => $date,
        ];

        foreach ($assessment->getResults() as $response) {
          $policy = $response->getPolicy();
          $dataset_name = $this->getPolicyDatasetName($policy);

          $insert[$dataset_name][] =  $this->getPolicyDatasetValues($policy, $response);

          $assessment_row = $defaults;
          $assessment_row['policy_name'] = $policy->name;
          $assessment_row['policy_title'] = $policy->title;
          $assessment_row['language'] = $policy->language;
          $assessment_row['type'] = $policy->type;
          $assessment_row['result_type'] = $response->getType();
          $assessment_row['result_severity'] = $response->getSeverity();
          $insert['Drutiny_assessment_results'][] = $assessment_row;
        }

        $this->datasets =  $insert;
        return $this;
    }

    public function write():iterable
    {
        $logger = $this->container->get('logger');

        // Append new rows.
        foreach ($this->datasets as $dataset_name => $rows) {
            // Ensure the cell order matches the schema.
            $writer = Writer::createFromString();
            $writer->setEscape('');
            $writer->insertOne(array_keys($rows[0]));
            $writer->insertAll($rows);
            $writer->setNewline("\r\n");
            //RFC4180Field::addTo($writer);
            $logger->info("Appending rows into $dataset_name.");

            $filepath = $this->directory . '/' . $dataset_name . '__' . $this->namespace . '.' . $this->extension;
            $stream = new StreamOutput(fopen($filepath, 'w'));
            $stream->write($writer->getContent());
            $this->logger->info("Written $filepath.");
            yield $filepath;
        }
    }

    public function getPolicyDatasetName(Policy $policy)
    {
        return 'Drutiny_Policy_'.strtr($policy->name, [
          ':' => '_',
          ]).'_results';
    }

    public function getPolicyDatasetValues(Policy $policy, AuditResponse $response)
    {
        $row = [
          'assessment_uuid' => '',
          'target' => $this->container->get('target')['drush.alias'],
          'title' => $policy->title,
          'name' => $policy->name,
          'class' => $policy->class,
          'description' => $policy->description,
          'language' => $policy->language,
          'type' => $policy->type,
          'tags' => implode(',', $policy->tags),
          'severity' => $policy->severity,

          'result_type' => $response->getType(),
          'result_severity' => $response->getSeverity(),
          'result_date' => date('c', REQUEST_TIME),
        ];

        foreach ($policy->parameters as $key => $value) {
          $row['parameters_' . $key] = $this->prepareValue($value);
        }

        foreach ($response->getTokens() as $key => $value) {
          $row['result_token_' . $key] = $this->prepareValue($value);
        }
        return $row;
    }

    protected function prepareValue($value) {
      switch (gettype($value)) {
         case 'string':
         case 'integer':
         case 'double':
         case 'boolean':
             return $value;
             break;
         default:
             return json_encode($value);
             break;
      }
      return $column;
    }

    private function uuid()
    {
      $data = random_bytes(16);
      $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

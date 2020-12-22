<?php

namespace Drutiny\Audit\Filesystem;

use Drutiny\Audit;
use Drutiny\Sandbox\Sandbox;
use Drutiny\Audit\AbstractAnalysis;
use Drutiny\Annotation\Param;

class CodeScanAnalysis extends AbstractAnalysis
{

    public function configure()
    {
        $this->addParameter(
            'directory',
            static::PARAMETER_OPTIONAL,
            'Absolute filepath to directory to scan',
            '%root'
        );
        $this->addParameter(
            'exclude',
            static::PARAMETER_OPTIONAL,
            'Absolute filepaths to directories omit from scanning',
        );
        $this->addParameter(
            'filetypes',
            static::PARAMETER_OPTIONAL,
            'file extensions to include in the scan',
        );
        $this->addParameter(
            'patterns',
            static::PARAMETER_OPTIONAL,
            'patterns to run over each matching file.',
        );
        $this->addParameter(
            'whitelist',
            static::PARAMETER_OPTIONAL,
            'Whitelist patterns which the \'patterns\' parameter may yield false positives from',
        );
        $this->addParameter(
            'expression',
            static::PARAMETER_REQUIRED,
            'The expression language to evaluate. See https://symfony.com/doc/current/components/expression_language/syntax.html'
        );
        $this->addParameter(
            'variables',
            static::PARAMETER_OPTIONAL,
            'A keyed array of expressions to set variables before evaluating the passing expression.',
            []
        );
        $this->addParameter(
            'syntax',
            static::PARAMETER_OPTIONAL,
            'expression_language or twig',
            'expression_language'
        );
        $this->addParameter(
            'not_applicable',
            static::PARAMETER_OPTIONAL,
            'The expression language to evaludate if the analysis is not applicable. See https://symfony.com/doc/current/components/expression_language/syntax.html',
            'false'
        );
    }


  /**
   * @inheritdoc
   */
    public function gather(Sandbox $sandbox)
    {
        $directory = $this->getParameter('directory', '%root');
        $stat = $sandbox->drush(['format' => 'json'])->status();

        // Backwards compatibility. %paths is no longer present since Drush 8.
        if (!isset($stat['%paths'])) {
            foreach ($stat as $key => $value) {
              $stat['%paths']['%'.$key] = $value;
            }
        }

        $directory =  strtr($directory, $stat['%paths']);

        $command = ['find', $directory, '-type f'];

        $types = $this->getParameter('filetypes', []);

        if (!empty($types)) {
            $conditions = [];
            foreach ($types as $type) {
                $conditions[] = '-iname "*.' . $type . '"';
            }
            $command[] = '\( ' . implode(' -or ', $conditions) . ' \)';
        }

        foreach ($this->getParameter('exclude', []) as $filepath) {
            $filepath = strtr($filepath, $stat['%paths']);
            $command[] = "! -path '$filepath'";
        }

        $command[] = '| (xargs grep -nE';
        $command[] = '"' . implode('|', $this->getParameter('patterns', [])) . '" || exit 0)';

        $whitelist = $this->getParameter('whitelist', []);
        if (!empty($whitelist)) {
            $command[] = "| (grep -vE '" . implode('|', $whitelist) . "' || exit 0)";
        }

        $command = implode(' ', $command);
        $sandbox->logger()->info('[' . __CLASS__ . '] ' . $command);
        $output = $this->target->getService('exec')->run($command);

        $this->set('has_results', !empty($output));

        $matches = array_filter(explode(PHP_EOL, $output));
        $matches = array_map(function ($line) {
            list($filepath, $line_number, $code) = explode(':', $line, 3);
            return [
            'file' => $filepath,
            'line' => $line_number,
            'code' => trim($code),
            'basename' => basename($filepath)
            ];
        }, $matches);

        $results = [
          'found' => count($matches),
          'findings' => $matches,
          'filepaths' => array_values(array_unique(array_map(function ($match) use ($stat) {
              return str_replace($stat['%paths']['%root'], '', $match['file']);
          }, $matches)))
        ];

        $this->set('results', $results);
    }
}

<?php

namespace Drutiny\Report\Format;

use Drutiny\Registry;
use Drutiny\Profile;
use Drutiny\Target\Target;
use Symfony\Component\Yaml\Yaml;


class Markdown extends JSON {

  /**
   * The content to use when rendering Markdown.
   *
   * @var array
   */
  protected $content = [];

  /**
   * The twig template to use to render the report wrapper in HTML.
   *
   * @var string
   */
  protected $template = 'page';

  public function __construct($options)
  {
    parent::__construct($options);
    $this->setFormat('markdown');

    $this->setTemplate(isset($options['template']) ? $options['template'] : 'page');

    if (!isset($options['content'])) {
      $options['content'] = Yaml::parseFile(dirname(__DIR__) . '/templates/content/profile.markdown.yml');
    }
    $this->setContent($options['content']);
  }

  /**
   * Get the profile title.
   */
  public function getTemplate()
  {
    return $this->template;
  }

  /**
   * Set the title of the profile.
   */
  public function setTemplate($template)
  {
    $this->template = $template;
    return $this;
  }

  /**
   * Get the profile title.
   */
  public function getContent()
  {
    return $this->content;
  }

  /**
   * Set the title of the profile.
   */
  public function setContent(array $content)
  {
    $this->content = $content;
    return $this;
  }


  protected function preprocessResult(Profile $profile, Target $target, array $result)
  {
    $render = parent::preprocessResult($profile, $target, $result);

    // Render any markdown into HTML for the report.
    foreach ($render['results'] as &$result) {
      // Produce an ID for the result that can be used as an HTML ID attribute.
      $result['id'] = preg_replace('/[^0-9a-zA-Z]/', '', $result['title']);

      $results_vars = ['result' => $result];
      $result_render = $this->renderTemplate($result['type'], $results_vars);
      $render['output_' . $result['type']][] = $result_render;
      $result['rendered_result'] = $result_render;
    }

    $render['summary_table']  = $this->renderTemplate('summary_table', $render);
    $render['appendix_table'] = $this->renderTemplate('appendix_table', $render);
    $render['severity_stats'] = $this->renderTemplate('severity_stats', $render);

    $engine = new \Mustache_Engine();
    foreach ($this->getContent() as $idx => $section) {
      try {
         $section = '## ' . $section['heading'] . PHP_EOL . $engine->render($section['body'], $render);
      }
      catch (\Mustache_Exception $e) {
        throw new \Exception("Error in " . __CLASS__ . ": " . $e->getMessage());
      }
      $render['sections'][] = $section;
    }
    return $render;
  }

  protected function renderResult(array $variables)
  {
    return $this->processRender($this->renderTemplate('site', $variables), $variables);
  }

  protected function preprocessMultiResult(Profile $profile, Target $target, array $results)
  {
    $vars = parent::preprocessMultiResult($profile, $target, $results);
    $render = [
      'title' => $profile->getTitle(),
      'description' => $profile->getDescription(),
      'summary' => 'Report audits policies over ' . count($results) . ' sites.',
      'domain' => 'Multisite report'
    ];
    return array_merge($render, $vars);
  }

  protected function renderMultiResult(array $variables)
  {
    return $this->processRender($this->renderTemplate('multisite', $variables), $variables);
  }

  protected function processRender($content, $render)
  {

    // Render the header/footer etc.
    $render['content'] = $content;
    $content = $this->renderTemplate($this->getTemplate(), $render);

    return $this->formatTables($content);
  }

  public static function formatTables($markdown)
  {
    $lines = explode(PHP_EOL, $markdown);
    $table = [
    'start' => NULL,
    'widths' => [],
    'rows' => [],
    ];

    foreach ($lines as $idx => $line) {

      if ($table['start'] === NULL) {
        if (strpos($line, ' | ') !== FALSE) {
          $table['start'] = $idx;
        }
        else {
          continue;
        }
      }
      elseif (strpos($line, ' | ') === FALSE) {

        foreach ($table['rows'] as $line_idx => $row) {
          $widths = $table['widths'];

          foreach ($row as $i => $value) {
            $pad = array_search($line_idx, array_keys($table['rows'])) === 1 ? '-' : ' ';
            $row[$i] = str_pad($value, $table['widths'][$i], $pad, STR_PAD_RIGHT);
          }
          $lines[$line_idx] = implode(' | ', $row);
        }

        $table['start']  = NULL;
        $table['widths'] = [];
        $table['rows']   = [];
        continue;
      }

      $cells = array_map('trim', explode('|', $line));

      foreach ($cells as $i => $value) {
        if (!isset($table['widths'][$i])) {
          $table['widths'][$i] = strlen($value);
        }
        else {
          $table['widths'][$i] = max($table['widths'][$i], strlen($value));
        }
      }
      $table['rows'][$idx] = $cells;
    }

    return implode(PHP_EOL, $lines);
  }
}

 ?>

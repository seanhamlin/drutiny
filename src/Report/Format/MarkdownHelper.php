<?php

namespace Drutiny\Report\Format;

use Parsedown;

class MarkdownHelper extends Parsedown
{

  // Remove Code as a block type. This prevents code blocks occuring
  // from indentation as it becomes confusing in YAML template files.
    protected $unmarkedBlockTypes = array('Foo');

  // $unmarkedBlockTypes cannot be empty due to a parsing bug so blockFoo() must
  // be defined.
    protected function blockFoo()
    {
        return;
    }

    protected function blockTable($Line, array $Block = null)
    {
      $block = parent::blockTable($Line, $Block);
      if (isset($block['element']['name'])) {
        $block['element']['attributes']['class'] = 'table table-hover';
      }

      return $block;
    }
}

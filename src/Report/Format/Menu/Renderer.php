<?php

namespace Drutiny\Report\Format\Menu;

use Knp\Menu\Renderer\ListRenderer;
use Knp\Menu\ItemInterface;

class Renderer extends ListRenderer {
  protected function renderList(ItemInterface $item, array $attributes, array $options)
  {
      $attributes['class'] = $item->isRoot() ? 'nav navbar-nav' : 'dropdown-menu';
      return parent::renderList($item, $attributes, $options);
  }

  protected function renderLink(ItemInterface $item, array $options = array())
  {

    if ($item->hasChildren()) {
      $item->setLinkAttribute('data-toggle', 'dropdown');
      $item->setLinkAttribute('data-target', '#');
    }

    return parent::renderLink($item, $options);
  }

  protected function renderItem(ItemInterface $item, array $options)
  {
    if ($item->getChildren() && $item->getLevel() > 1) {
      $item->setAttribute('class', 'dropdown-submenu');
    }
    return parent::renderItem($item, $options);
  }
}

 ?>

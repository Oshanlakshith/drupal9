<?php

namespace Drupal\system_page_override;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the types of system pages.
 */
class SystemPages {

  const FRONT = 'front';
  const NOT_FOUND = '404';
  const FORBIDDEN = '403';

  /**
   * Returns a array of system pages.
   *
   * @return array
   *   Returns array of system pages with their labels.
   */
  public static function getLabels(): array {
    return [
      self::FRONT => new TranslatableMarkup('Home page'),
      self::FORBIDDEN => new TranslatableMarkup('403 page'),
      self::NOT_FOUND => new TranslatableMarkup('404 page'),
    ];
  }

  /**
   * Returns a label for a system page.
   *
   * @param string $page
   *   A system page type.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns a translatable markup of the label of the system page.
   */
  public static function getLabel(string $page): TranslatableMarkup {
    return self::getLabels()[$page];
  }

}

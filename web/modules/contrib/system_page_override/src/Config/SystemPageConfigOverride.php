<?php

namespace Drupal\system_page_override\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\system_page_override\SystemPageManager;
use Drupal\system_page_override\SystemPages;

/**
 * Defines the system page config override.
 */
class SystemPageConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The system page manager service.
   *
   * @var \Drupal\system_page_override\SystemPageManager
   */
  protected $service;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * System page config override constructor.
   *
   * @param \Drupal\system_page_override\SystemPageManager $service
   *   The System page manager service.
   */
  public function __construct(SystemPageManager $service, LanguageManagerInterface $language_manager) {
    $this->service = $service;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if (!in_array('system.site', $names)) {
      return [];
    }
    $language_id = $this->languageManager->getCurrentLanguage()->getId();
    $entries['system.site']['page'] = [];

    $front = $this->service->getOverride(SystemPages::FRONT, $language_id);
    $not_found = $this->service->getOverride(SystemPages::NOT_FOUND, $language_id);
    $forbidden = $this->service->getOverride(SystemPages::FORBIDDEN, $language_id);

    if ($front) {
      $entries['system.site']['page'][SystemPages::FRONT] = $front;
    }
    if ($not_found) {
      $entries['system.site']['page'][SystemPages::NOT_FOUND] = $not_found;
    }
    if ($forbidden) {
      $entries['system.site']['page'][SystemPages::FORBIDDEN] = $forbidden;
    }
    if (empty($entries['system.site']['page'])) {
      return [];
    }
    return $entries;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    $metadata
      ->setCacheContexts(['languages:language_interface'])
      ->addCacheTags(['config:system.site']);
    return $metadata;
  }

}

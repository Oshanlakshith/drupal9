<?php

namespace Drupal\system_page_override;

use Drupal\Core\Cache\Cache;
use Drupal\Core\State\StateInterface;

/**
 * Defines the System page override system page manager.
 */
class SystemPageManager {

  protected const KEY_PREFIX = 'system_page_override';

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Provides a key containing the provided page and langcode.
   *
   * @param string $page
   *   The system page type.
   * @param string $langcode
   *   The langcode.
   *
   * @return string
   *   Returns the key used to save a system page path in Drupal state.
   */
  protected function getStateKey(string $page, string $langcode): string {
    return self::KEY_PREFIX . ':' . $page . ':' . $langcode;
  }

  /**
   * Sets the path for a system page in Drupal State.
   *
   * @param string $page
   *   The system page type.
   * @param string $langcode
   *   The langcode.
   * @param string $node_path
   *   The node path that is set in the state.
   */
  public function override(string $page, string $langcode, string $node_path) {
    $this->state->set($this->getStateKey($page, $langcode), $node_path);

    $this->clearFrontpageCache();
  }

  /**
   * Deletes the path for a system page and clears the front page cache.
   *
   * @param string $page
   *   The system page type.
   * @param string $langcode
   *   The langcode.
   */
  public function revert(string $page, string $langcode) {
    $this->state->delete($this->getStateKey($page, $langcode));

    $this->clearFrontpageCache();
  }

  /**
   * Returns if the system page is already overridden for a language.
   *
   * @param string $page
   *   The system page type.
   * @param string $langcode
   *   The langcode.
   *
   * @return bool
   *   Returns if a system page is already overwritten.
   */
  public function isOverridden(string $page, string $langcode): bool {
    return $this->state->get($this->getStateKey($page, $langcode)) !== NULL;
  }

  /**
   * Returns the path for a system page for a language.
   *
   * @param string $page
   *   The system page type.
   * @param string $langcode
   *   The langcode.
   *
   * @return string|null
   *   Returns the path for a system page if present, otherwise null.
   */
  public function getOverride(string $page, string $langcode): ?string {
    return $this->state->get($this->getStateKey($page, $langcode));
  }

  /**
   * Returns if the given path is the current path in Drupal state.
   *
   * @param string $page
   *   The system page type.
   * @param string $langcode
   *   The langcode.
   * @param string $node_path
   *   The node path that is checked with the path in the state.
   *
   * @return bool
   *   Returns if the given path is set in Drupal state for the given page.
   */
  public function isOverride(string $page, string $langcode, string $node_path): bool {
    return $this->getOverride($page, $langcode) == $node_path;
  }

  /**
   * Invalidates cache tags.
   */
  protected function clearFrontpageCache() {
    // @todo could we only invalidate homepage cache?
    Cache::invalidateTags([
      'config:system.site',
      'route_match',
      'http_response',
    ]);
  }

  /**
   * Returns a canonical path for a node.
   *
   * @param string $node_id
   *   The node id used to create a canonical path to that node.
   *
   * @return string
   *   Returns canonical path as a string.
   */
  public function createPath(string $node_id): string {
    return '/node/' . $node_id;
  }

}

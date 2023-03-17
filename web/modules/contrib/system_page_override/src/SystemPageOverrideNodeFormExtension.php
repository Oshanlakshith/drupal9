<?php

namespace Drupal\system_page_override;

use Drupal\Core\Config\Config;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the system page override node form extension.
 */
class SystemPageOverrideNodeFormExtension implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The System page manager service.
   *
   * @var \Drupal\system_page_override\SystemPageManager
   */
  private $systemPageService;

  /**
   * The Drupal config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * The Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * SystemPageOverrideNodeFormExtension constructor.
   *
   * @param SystemPageManager $system_page_override_service
   *   The System page manager service.
   * @param \Drupal\Core\Config\Config $config
   *   The Drupal config.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The Language manager.
   */
  public function __construct(SystemPageManager $system_page_override_service, Config $config, LanguageManagerInterface $language_manager, AccountInterface $current_user) {
    $this->systemPageService = $system_page_override_service;
    $this->config = $config;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system_page_override.manager'),
      $container->get('config.factory')->get('system_page_override.settings'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * Alters the edit form of a node.
   *
   * @param array $form
   *   The node form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The node's form state.
   */
  public function alter(array &$form, FormStateInterface $form_state) {
    if (!$this->currentUser->hasPermission('administer node as system page')) {
      return;
    }

    /** @var \Drupal\node\NodeForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_object->getEntity();

    if (!$this->needsAltering($node)) {
      return;
    }

    $form['system_page_override'] = [
      '#type' => 'details',
      '#title' => $this->t('Systempage settings'),
      '#group' => 'advanced',
      '#weight' => 100,
      '#open' => FALSE,
      '#tree' => TRUE,
    ];

    $languages = $this->languageManager->getLanguages();

    foreach ($languages as $language) {

      $front_checked = $this->isChecked(SystemPages::FRONT, $language, $node);
      $not_found_checked = $this->isChecked(SystemPages::NOT_FOUND, $language, $node);
      $forbidden_checked = $this->isChecked(SystemPages::FORBIDDEN, $language, $node);

      if ($this->needsAltering($node, SystemPages::FRONT)) {
        $form['system_page_override'][SystemPages::FRONT][$language->getId()] =
          $this->getCheckbox(SystemPages::FRONT, $language, $front_checked);
        if ($front_checked) {
          $form['system_page_override']['#open'] = TRUE;
        }
      }

      if ($this->needsAltering($node, SystemPages::NOT_FOUND)) {
        $form['system_page_override'][SystemPages::NOT_FOUND][$language->getId()] =
          $this->getCheckbox(SystemPages::NOT_FOUND, $language, $not_found_checked);
        if ($not_found_checked) {
          $form['system_page_override']['#open'] = TRUE;
        }
      }

      if ($this->needsAltering($node, SystemPages::FORBIDDEN)) {
        $form['system_page_override'][SystemPages::FORBIDDEN][$language->getId()] =
          $this->getCheckbox(SystemPages::FORBIDDEN, $language, $forbidden_checked);
        if ($forbidden_checked) {
          $form['system_page_override']['#open'] = TRUE;
        }
      }
    }

    foreach (array_keys($form['actions']) as $action) {
      if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
        $form['actions'][$action]['#submit'][] = [static::class, 'submit'];
      }
    }
  }

  /**
   * Form submission handler for menu item field on the node form.
   *
   * @param array $form
   *   The node form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the altered node.
   */
  public static function submit(array $form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('system_page_override')) {
      return;
    }

    $values = $form_state->getValue('system_page_override');
    $system_page_override_service = \Drupal::service('system_page_override.manager');

    $form_object = $form_state->getFormObject();
    assert($form_object instanceof ContentEntityFormInterface);
    $node = $form_object->getEntity();
    assert($node instanceof NodeInterface);

    foreach ($values as $page => $languages) {
      foreach ($languages as $language => $value) {
        if ($value) {
          $system_page_override_service->override($page, $language, $system_page_override_service->createPath($node->id()));
        }
        elseif ($system_page_override_service->isOverride($page, $language, $system_page_override_service->createPath($node->id()))) {
          $system_page_override_service->revert($page, $language);
        }
      }
    }
  }

  /**
   * Checks whether this node is used as system page.
   *
   * @param string $page
   *   The system page type.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   * @param \Drupal\node\NodeInterface $node
   *   The node that is altered.
   *
   * @return bool
   *   Returns a bool whether the current node is configured as system page.
   */
  private function isChecked(string $page, LanguageInterface $language, NodeInterface $node) : bool {
    return !$node->isNew() && $this->systemPageService->isOverride($page, $language->getId(), $this->systemPageService->createPath($node->id()));
  }

  /**
   * Builds a checkbox to use this node as system page.
   *
   * @param string $page
   *   The system page type.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language.
   * @param bool $checked
   *   The boolean which tells if the checkbox needs to be checked.
   *
   * @return array
   *   Returns a render array containing a checkbox.
   */
  private function getCheckbox(string $page, LanguageInterface $language, bool $checked) : array {
    $isMultiLingual = $this->languageManager->isMultilingual();
    $form = [
      '#type' => 'checkbox',
      '#title' => $isMultiLingual
      ? $this->t('@page for %language', ['%language' => $language->getName(), '@page' => SystemPages::getLabel($page)])
      : SystemPages::getLabel($page),
      '#default_value' => $checked,
    ];
    return $form;
  }

  /**
   * Checks whether the node is configurable as system page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that is altered.
   * @param string $page
   *   The system page type.
   *
   * @return bool
   *   Returns a boolean whether the node is configurable as system page.
   */
  protected function needsAltering(NodeInterface $node, $page = '') : bool {
    if (empty($page)) {
      $front = in_array($node->bundle(), $this->config->get('enabled_node_bundles_' . SystemPages::FRONT) ?: []);
      $not_found = in_array($node->bundle(), $this->config->get('enabled_node_bundles_' . SystemPages::NOT_FOUND) ?: []);
      $forbidden = in_array($node->bundle(), $this->config->get('enabled_node_bundles_' . SystemPages::FORBIDDEN) ?: []);
      return $front || $not_found || $forbidden;
    }
    else {
      return in_array($node->bundle(), $this->config->get('enabled_node_bundles_' . $page) ?: []);
    }
  }

}

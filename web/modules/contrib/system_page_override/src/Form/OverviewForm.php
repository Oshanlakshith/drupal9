<?php

namespace Drupal\system_page_override\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\system_page_override\SystemPageManager;
use Drupal\system_page_override\SystemPages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form where all system pages can be overridden with paths.
 */
class OverviewForm extends ConfigFormBase {

  /**
   * The System page manager service.
   *
   * @var \Drupal\system_page_override\SystemPageManager
   */
  private $systemPageManager;

  /**
   * The Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * OverviewForm constructor.
   *
   * @param \Drupal\system_page_override\SystemPageManager $system_page_manager
   *   A System page manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   A Language manager service.
   */
  public function __construct(SystemPageManager $system_page_manager, LanguageManagerInterface $language_manager) {
    $this->systemPageManager = $system_page_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system_page_override.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system_page_override.overview'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_page_override_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach (SystemPages::getLabels() as $page_id => $page_label) {
      $form[] = $this->getSystemPageTextfields($page_id);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (SystemPages::getLabels() as $page_id => $page_label) {
      foreach ($form_state->getValue($page_id) as $language_id => $page_path) {
        $this->systemPageManager->override($page_id, $language_id, $page_path);
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns textfields for configurable system page paths.
   *
   * @param string $page
   *   A system page type.
   *
   * @return array
   *   Returns a array of textfields.
   */
  protected function getSystemPageTextfields(string $page) {
    $textfields = [];
    $languages = $this->languageManager->getLanguages();
    $textfields[$page] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('System pages for the %page', ['%page' => SystemPages::getLabel($page)]),
      '#description' => $this->t('Set the %page per language', ['%page' => SystemPages::getLabel($page)]),
      '#tree' => TRUE,
    ];
    foreach ($languages as $language) {
      $textfields[$page][$language->getId()] = [
        '#type' => 'textfield',
        '#title' => $this->t('@page for %language', [
          '%language' => $language->getName(),
          '@page' => SystemPages::getLabel($page),
        ]),
        '#default_value' => $this->systemPageManager->getOverride($page, $language->getId()),
      ];
    }
    return $textfields;

  }

}

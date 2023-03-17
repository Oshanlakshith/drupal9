<?php

namespace Drupal\system_page_override\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system_page_override\SystemPages;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form where content types can be configured as editable.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * NodeStorage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeTypeStorage;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeTypeStorage = $entity_type_manager->getStorage('node_type');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system_page_override.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_page_override_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('system_page_override.settings');
    $options = $this->getContentEntityTypes();

    foreach (SystemPages::getLabels() as $page_id => $page_label) {
      $enabled_node_bundles = $config->get('enabled_node_bundles_' . $page_id) ?: [];
      $form['enabled_node_bundles'][$page_id] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t('Enabled content entity type for @page', ['@page' => $page_label]),
        '#description' => $this->t('Enable content entities which can be configured as @page', ['@page' => $page_label]),
        '#tree' => TRUE,
      ];
      foreach ($options as $entity_id => $entity_label) {
        $form['enabled_node_bundles'][$page_id][$entity_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_label,
          '#default_value' => in_array($entity_id, $enabled_node_bundles),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('system_page_override.settings');
    foreach (SystemPages::getLabels() as $page_id => $page_label) {
      $config->set('enabled_node_bundles_' . $page_id, array_keys(array_filter($form_state->getValue($page_id))));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns all content entity types that are available.
   */
  private function getContentEntityTypes() {
    $node_types = $this->nodeTypeStorage->loadMultiple();
    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }
    return $options;
  }

}

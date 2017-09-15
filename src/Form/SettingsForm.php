<?php

namespace Drupal\layout_per_node\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\layout_per_node\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lpn_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'layout_per_node.settings.module_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('layout_per_node.settings.module_settings');

    $form['description'] = [
      '#markup' => 'General configuration options for the Layout Per Node module.',
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => t('Content Settings'),
      '#open' => TRUE,
      '#description' => 'Choose which types of content should be made available when using Layout Per Node to assign content to regions.',
    ];

    $modules = [];
    // Used to get human-readable module name.
    $moduleHandler = \Drupal::moduleHandler();
    // All available module blocks.
    $definitions = $this->getBuildOptions();
    foreach ($definitions as $id => $definition) {
      // Accumulate modules and blocks.
      $modules[$definition['provider']][$id] = $definition['admin_label'];
    }

    // Create form element for each module, listing block names for examples.
    foreach ($modules as $machine_name => $block) {
      $examples = "ex: ";
      $form['options'][$machine_name] = [
        '#type' => 'checkbox',
        '#default_value' => $config->get($machine_name),
      ];
      // Limit number of example block names to 5.
      $count = 0;
      foreach ($block as $id => $name) {
        if ($count == 5) {
          break;
        }
        // Remove "Broken/Missing" block from examples to reduce confusion.
        if ($id != 'broken') {
          $examples .= $name . ", ";
          $count++;
        }
      }

      $form['options'][$machine_name]['#title'] = $moduleHandler->getName($machine_name) . "  (" . substr($examples, 0, -2) . ")";
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $definitions = $this->getBuildOptions();

    foreach ($definitions as $id => $definition) {
      $this->config('layout_per_node.settings.module_settings')
        ->set($definition['provider'], $values[$definition['provider']])
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to determine available content from other modules.
   */
  protected function getBuildOptions() {
    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');
    return $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());
  }

}

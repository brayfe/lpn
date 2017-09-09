<?php

namespace Drupal\layout_per_node\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class SwitchLayoutsForm.
 *
 * @package Drupal\layout_per_node\Form
 */
class SwitchLayoutsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::request()->query->all();
    if (isset($query['id'])) {
      $node = Node::load($query['id']);
      $layoutPluginManager = \Drupal::service('plugin.manager.core.layout');
      $config = \Drupal::config('layout_per_node.allowed');
      $allowed = array_filter($config->get($node->getType()));
      $options = $layoutPluginManager->getLayoutOptions();
      $definitions = $layoutPluginManager->getDefinitions();
      $options = [];
      $module_handler = \Drupal::service('module_handler');
      $module_path = $module_handler->getModule('layout_per_node')->getPath();
      foreach ($definitions as $key => $definition) {
        $category = $definition->get('category');
        $icon = $definition->get('icon');
        $options[$category][$key] = $definition->get('label');
        if ($icon) {
          $preview[$key] = $icon;
        }
        else {
          $preview[$key] = $module_path . '/images/preview.png';
        }
        if (!empty($allowed)) {
          if (!in_array($key, $allowed)) {
            unset($options[$category][$key]);
            unset($preview[$key]);
          }
        }
      }
      // Check to see if there is per-node data. If so, use the zeroth element.
      $layout = NULL;
      $layoutPerNodeManager = \Drupal::service('layout_per_node.manager');
      if ($layout_entity = $layoutPerNodeManager->getLayoutEntity($node->id(), $node->vid->value)) {
        $layout_raw = $layout_entity->get('layout')->getValue();
        if ($node && $layout_raw) {
          if (!empty(key($layout_raw[0]))) {
            $layout = key($layout_raw[0]);
          }
        }
      }

      $preview_markup = '<div class="template-preview">';
      foreach ($preview as $key => $icon) {
        $preview_markup .= '<img id="preview-' . $key . '" src="/' . $icon . '" />';
      }
      $preview_markup .= '</div>';
      $form['preview']['#markup'] = $preview_markup;

      $form['layout'] = [
        '#title' => $this->t('Layout'),
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $layout,
      ];

      $form['#attached']['library'][] = 'layout_per_node/switch_layouts';
      $form['submit'] = [
        '#markup' => '<div class="button js-form-submit form-submit btn btn-primary" data-layout-editor-switch="1" data-nid="' . $query['id'] . '">Apply this layout</div>',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_per_node_switch_layouts';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}

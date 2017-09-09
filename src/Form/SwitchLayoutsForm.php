<?php

namespace Drupal\layout_per_node\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class AddContentForm.
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
      // Filter only user-defined layouts, if any have been selected.
      if (!empty($allowed)) {
        foreach ($options as $category => $layouts) {
          foreach ($layouts as $key => $label) {
            if (!in_array($key, $allowed)) {
              unset($options[$category][$key]);
            }
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
            $layout = $layout_raw[0];
          }
        }
      }

      $form['layout'] = [
        '#title' => $this->t('Layout'),
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $layout,
      ];
      // @todo -- default to selected value.
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

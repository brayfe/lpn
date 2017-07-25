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
class AddContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_per_node_add_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getParameters() {
    $return = array();
    $request_path = \Drupal::service('path.current')->getPath();
    $parts = explode('/', $request_path);
    if (isset($parts[4])) {
      $return['nid'] = $parts[4];
    }
    if (isset($parts[5])) {
      $return['region'] = $parts[5];
    }
    return $return;
  }

  /**
   * Form: list fields, content blocks, & views blocks for placement on a page.
   *
   * Note: this returns all fields that have content. The JS method
   * Drupal.behaviors.layoutEditorAddContent strips those which are already
   * present in the layout.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parameters = $this->getParameters();
    if (isset($parameters['nid'])) {
      $form['tabs'] = array(
        '#prefix' => '<div class="one-third">',
        '#type' => 'vertical_tabs',
        '#title' => t('Settings'),
        '#suffix' => '</div>',
      );
      $form['fields'] = array(
        '#type' => 'details',
        '#title' => t('Fields (page-specific)'),
        '#group' => 'tabs',
      );
      $form['fields'] += $this->buildFields($parameters['nid']);
      $form['content_blocks'] = array(
        '#type' => 'details',
        '#title' => t('Blocks (site-wide)'),
        '#group' => 'tabs',
      );
      $form['content_blocks'] += $this->buildContentBlocks();
      $form['views_blocks'] = array(
        '#type' => 'details',
        '#title' => t('Lists (Views)'),
        '#group' => 'tabs',
      );
      $form['views_blocks'] += $this->buildViewsBlocks();
      $form += $this->addPreview();
      $form['submit'] = [
        '#markup' => '<div class="button js-form-submit form-submit btn btn-primary" data-layout-editor-add="1" data-region="' . $parameters['region'] . '">Place on page</div>',
        '#allowed_tags' => array('div'),
      ];
      $form['#attached']['library'][] = 'layout_per_node/layout_editor';
      $form['#attached']['library'][] = 'layout_per_node/add_content';
    }
    return $form;
  }

  /**
   * Custom callback to create markup with available fields.
   */
  protected function buildContentBlocks() {
    $form = [];
    $type = 'block_content';
    $blocks = \Drupal::entityManager()->getStorage($type)->loadMultiple();
    if (!empty($blocks)) {
      foreach ($blocks as $name => $block) {
        $id = $block->uuid();
        $form[$id]['#markup'] = $this->renderButton($id, $block->label(), $type);
      }
    }
    return $form;
  }

  /**
   * Custom callback to create markup with available Views blocks.
   */
  protected function buildViewsBlocks() {
    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());
    foreach ($definitions as $id => $definition) {
      if ($definition['provider'] == 'views') {
        $form[$id]['#markup'] = $this->renderButton($id, $definition['admin_label'], 'plugin_block');
      }
    }
    return $form;
  }

  /**
   * Custom callback to create markup with available fields.
   */
  protected function buildFields($nid) {
    $fields = $this->getAvailableFields($nid);
    foreach ($fields as $field => $label) {
      $form[$field]['#markup'] = $this->renderButton($field, $label, 'field');
    }
    return $form;
  }

  /**
   * Custom callback to retrieve available fields.
   */
  protected function getAvailableFields($nid = NULL) {
    $return = [];
    $disallowed = ['promote'];
    if ($entity = Node::load($nid)) {
      $display = entity_get_display('node', $entity->getType(), 'default');
      $displayed_fields = array_keys($display->getComponents());
      $fields = $entity->getFields(FALSE);

      foreach ($fields as $field => $def) {
        // Only allow fields which are not disallowed (see above) AND
        // which are set to a non-hidden region in 'Manage View'.
        if (!in_array($field, $disallowed) && in_array($field, $displayed_fields)) {
          $definition = $entity->getFieldDefinition($field);
          if (!empty($definition->getTargetBundle())) {
            $fieldobj = $entity->{$field};
            // Only offer fields that will display with actual content.
            // This is admittedly a hacky way to verify empty content,
            // but isEmpty() will not work on complex fields.
            $view = $entity->$field->view(['label' => 'hidden']);
            $value = trim(strip_tags(render($view), '<a><img>'));
            if ($value != '') {
              $return[$field] = $definition->getLabel();
            }
          }
        }
      }
    }
    return $return;
  }

  /**
   * Create a "Preview" form element.
   */
  protected function addPreview() {
    $form['prev'] = [
      '#type' => 'fieldset',
      '#title' => 'Preview',
    ];
    $form['prev']['preview'] = [
      '#prefix' => '<div class="preview-box">',
      '#markup' => '<div data-layout-editor-preview="1"></div>',
      '#suffix' => '</div>',
    ];
    return $form;
  }

  /**
   * Build renderable HTML button.
   */
  protected function renderButton($id, $label, $type) {
    return '<div class="button js-form-submit form-submit btn btn-info btn-block" data-layout-editor-type="' . $type . '" data-layout-editor-addable="' . $id . '">' . $label . '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}

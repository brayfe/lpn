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
   * Form: list fields, content blocks, & views blocks for placement on a page.
   *
   * Note: this returns all fields that have content. The JS method
   * Drupal.behaviors.layoutEditorAddContent strips those which are already
   * present in the layout.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parameters = $this->getParameters();
    if (isset($parameters['id'])) {
      $form['tabs'] = [
        '#prefix' => '<div class="one-third">',
        '#type' => 'vertical_tabs',
        '#title' => t('Settings'),
        '#suffix' => '</div>',
      ];
      $form['fields'] = [
        '#type' => 'details',
        '#title' => t('Fields (page-specific)'),
        '#group' => 'tabs',
      ];
      $form['fields'] += $this->buildFields($parameters['id']);
      $form['content_blocks'] = [
        '#type' => 'details',
        '#title' => t('Custom Blocks (site-wide)'),
        '#group' => 'tabs',
      ];
      $form['content_blocks'] += $this->buildContentBlocks();

      // Determine which modules can provide content.
      $config = \Drupal::config('layout_per_node.settings')->get();
      $allowedContent = array_filter($config, function ($provider, $value) {
        return ($value && $value != '_core') ? $provider : NULL;}, ARRAY_FILTER_USE_BOTH);

      // Used to get human-readable module name.
      $moduleHandler = \Drupal::moduleHandler();
      // Build vertical tab for each module's blocks.
      foreach (array_keys($allowedContent) as $type) {
        $form[$type] = [
          '#type' => 'details',
          '#title' => $moduleHandler->getName($type),
          '#group' => 'tabs',
        ];
        // Get allowed blocks from module.
        $form[$type] += $this->buildOtherContent($type);
      }

      $form += $this->addPreview();
      $form['submit'] = [
        '#markup' => '<div class="button js-form-submit form-submit btn btn-primary" data-layout-editor-add="1" data-region="' . $parameters['region'] . '">Place on page</div>',
        '#allowed_tags' => ['div'],
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
   * Custom callback to create markup with available module blocks.
   */
  protected function buildOtherContent($type) {
    $form = [];
    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());

    foreach ($definitions as $id => $definition) {
      if ($definition['provider'] == $type) {
        $form[$id]['#markup'] = $this->renderButton($id, $definition['admin_label'], 'plugin_block');
      }
    }
    return $form;
  }

  /**
   * Custom callback to create markup with available fields.
   */
  protected function buildFields($nid) {
    $form = [];
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

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_per_node_add_content';
  }

  /**
   * Helper function to parse GET query parameters.
   */
  protected function getParameters() {
    $return = [];
    $query = \Drupal::request()->query->all();
    if (isset($query['id'])) {
      $return['id'] = $query['id'];
    }
    if (isset($query['region'])) {
      $return['region'] = $query['region'];
    }
    return $return;
  }

}

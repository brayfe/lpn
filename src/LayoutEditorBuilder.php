<?php

namespace Drupal\layout_per_node;

use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;
use Drupal\field_layout\FieldLayoutBuilder;
use Drupal\layout_per_node\Controller\LayoutEditorController;

/**
 * Builds a field layout.
 */
class LayoutEditorBuilder extends FieldLayoutBuilder {

  /**
   * Applies the layout to an entity build.
   *
   * @param array $build
   *   A renderable array representing the entity content or form.
   * @param \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface $display
   *   The entity display holding the display options configured for the entity
   *   components.
   */
  public function buildView(array &$build, EntityDisplayWithLayoutInterface $display) {

    // Set default node type display layout ID.
    $override = TRUE;
    $default_layout_id = $display->getLayoutId();
    $layout_id = $default_layout_id;
    $layout = [];

    // Ensure that query arguments affect the cache.
    $build['#cache']['contexts'] = [
      'user.permissions',
      'url.query_args:layout-editor',
      'url.query_args:layout',
    ];

    // Retrieve overrides passed in via query parameter or node data.
    $query = \Drupal::request()->query->all();
    $node = \Drupal::routeMatch()->getParameter('node');

    // If we are at /node/[nid]/revisions/[vid]/view, make sure to load all of
    // that revision's elements.
    if ($revision = $this->revisionView()) {
      $node = entity_revision_load('node', $revision);
    }

    // Check to see if there is per-node data. If so, use the zeroth element.
    if ($node && $layout_raw = $node->get('layout')->getValue()) {
      if (!empty(key($layout_raw[0]))) {
        $layout = $layout_raw[0];
        $layout_id = key($layout);
      }
    }

    // The user is in "Switch layouts" mode. Use that layout.
    if (isset($query['layout'])) {
      $layout_id = $query['layout'];
    }

    // If the node *does* have settings for this layout id, use them.
    if (isset($layout[$layout_id])) {
      $layout_from_node = $layout[$layout_id];
    }

    // At this point, the layout id is set, either from the switcher, the per
    // node data, or the node type default. The key dynamic behavior comes
    // from setting the $display layoutID, below.
    $layout_definition = $this->layoutPluginManager->getDefinition($layout_id, FALSE);
    if ($layout_definition) {
      // Ensure the layout ID is valid before trying to switch.
      $display->setLayoutID($layout_id);
    }
    else {
      $layout_definition = $this->layoutPluginManager->getDefinition($default_layout_id, FALSE);
    }

    // MAIN LOGIC HERE. Build and sort the node's content into regions.
    if ($layout_definition && $fields = $this->getFields($build, $display, 'view')) {
      // Add the regions to the $build in the correct order.
      $regions = array_fill_keys($layout_definition->getRegionNames(), []);
      // If there is dynamically set data for this layout, use it.
      if (!empty($layout[$layout_id]) && !empty($layout_from_node)) {
        foreach ($layout_from_node as $region => $contents) {
          foreach ($contents as $id => $type) {
            $layout_editor_mode = isset($query['layout-editor']) ? TRUE : FALSE;
            if ($type == 'field') {
              $regions[$region][$id] = $build[$id];
              if ($layout_editor_mode) {
                $regions[$region][$id]['#attributes']['data-layout-editor-type'] = $type;
                $regions[$region][$id]['#attributes']['data-layout-editor-object'] = $id;
              }
              unset($build[$id]);
            }
            else {
              $regions[$region][$id] = LayoutEditorController::buildContent($type, $id, $node->id(), $layout_editor_mode);
            }
          }
        }
        // Remove fields *not* set in the layout per node altogether.
        foreach ($fields as $name => $field) {
          unset($build[$name]);
        }
      }
      else {
        // Provide default region layout per content type configuration.
        foreach ($fields as $name => $field) {
          unset($build[$name]);
        }
      }

      // Provide the layout editor overlay if we are in "layout editor" mode.
      if (isset($query['layout-editor'])) {
        // Make the node ID available as a JavaScript variable.
        $build['#attached']['drupalSettings']['field_layout_editor']['nid'] = $node->id();
        $build['#attached']['library'][] = 'layout_per_node/block_place';
        $build['#attached']['library'][] = 'layout_per_node/layout_editor';
        $regions = $this->createAddContentButtons($node, $regions);
      }
      $return = $display->getLayout()->build($regions);

      // Finally, sort by weight provided by node layout.
      // This needs to happen after the build() method.
      foreach ($regions as $region => $values) {
        $inc = 0;
        foreach ($values as $key => $value) {
          $return[$region][$key]['#weight'] = $inc;
          $inc++;
        }
      }
      $build['_field_layout'] = $return;
    }
  }

  /**
   * Custom callback to populate "Add" buttons in each region.
   */
  public function createAddContentButtons($node, $regions) {
    $operations = [];
    foreach ($regions as $region => $values) {
      $title = t('<span class="visually-hidden">Place block in the %region region</span>', ['%region' => $region]);
      $operations['block_description'] = [
        '#weight' => 1000,
        '#type' => 'inline_template',
        '#template' => '<div class="block-place-region">{{ link }}</div>',
        '#context' => [
          'link' => Link::createFromRoute(
            $title,
            'layout_per_node.add_content',
            ['node' => $node->id(), 'region' => $region],
            [
              'attributes' => [
                'title' => $title,
                'class' => ['use-ajax', 'button', 'button--small'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode(['width' => 1000, 'max-height' => 'none']),
              ],
            ]
          ),
        ],
      ];
      $regions[$region]['block_place_operations'] = $operations;
    }
    return $regions;
  }

  /**
   * Simple boolean callback to check if this is a "revisions view" page.
   */
  protected function revisionView() {
    $revision = FALSE;
    $request_path = \Drupal::service('path.current')->getPath();
    $parts = explode('/', $request_path);
    if (isset($parts[5]) && $parts[5] == 'view' && isset($parts[3]) && $parts[3] == 'revisions') {
      $revision = $parts[4];
    }
    return $revision;
  }

}

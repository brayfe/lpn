<?php

namespace Drupal\layout_per_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;

/**
 * Class LayoutEditorController.
 *
 * AJAX get() and set() methods for handling content, as well as a
 * buildContent() method that can be used by LayoutEditorBuilder.
 */
class LayoutEditorController extends ControllerBase {

  /**
   * Reroutes requests for building content to their respective build functions.
   *
   * This is used in two places: via the AJAX callback
   * LayoutEditorController::content() and by LayoutEditorBuilder::buildView().
   *
   * @param string $type
   *   The entity type (e.g., field, block_content, plugin_block)
   * @param string $id
   *   The unique ID (e.g., field machine name or block UUID)
   * @param string $nid
   *   The node ID (needed to return node-specific fields)
   *
   * @return array
   *   A render array.
   */
  public static function buildContent($type, $id, $nid, $container = FALSE) {
    $content = '';
    switch ($type) {
      case 'field':
        $content = self::renderableField($nid, $id);
        break;

      case 'block_content':
        $content = self::renderableBlockContent($id);
        break;

      case 'plugin_block':
        $content = self::renderablePluginBlock($id);
        break;

    }
    if ($container) {
      $content = self::wrapInContainer($content, $id, $type);
    }
    return $content;
  }

  /**
   * Simple input-output method for returning a render array for fields.
   */
  protected static function renderableField($nid, $field_name) {
    $node = Node::load($nid);
    $entityType = 'node';
    // Before building a renderable object, we need to get the display of this
    // field in the context of the node settings.
    $display = entity_get_display($entityType, $node->getType(), 'default');
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entityType);
    $fieldRenderable = $viewBuilder->viewField($node->{$field_name}, $display->getComponent($field_name));
    return $fieldRenderable;
  }

  /**
   * Simple input-output method for returning a render array for content blocks.
   */
  protected static function renderableBlockContent($id) {
    $entity = \Drupal::entityManager()->loadEntityByUuid('block_content', $id);
    $block = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($entity);
    // The following runs the content block through the "block" theme function.
    // This allows modules like Quickedit to function.
    $block_render_array = [
      '#theme' => 'block',
      '#attributes' => [],
      '#contextual_links' => [],
      '#weight' => 1,
      '#configuration' => NULL,
      '#plugin_id' => NULL,
      '#base_plugin_id' => NULL,
      '#derivative_plugin_id' => NULL,
    ];
    // Take the attributes & contextual links values from the block content and
    // bubble them up to the block wrapper.
    foreach (['#attributes', '#contextual_links'] as $property) {
      if (isset($block[$property])) {
        $block_render_array[$property] += $block[$property];
        unset($block[$property]);
      }
    }
    $block_render_array['content'] = $block;
    return $block_render_array;
  }

  /**
   * Simple input-output method for returning a render array for plugin blocks.
   */
  protected static function renderablePluginBlock($id) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance($id, $config);
    // Some blocks might implement access check.
    $access_result = $plugin_block->access(\Drupal::currentUser());
    // Return empty render array if user doesn't have access.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      return [];
    }
    return $plugin_block->build();
  }

  /**
   * When content is displayed in Layout Editor, it needs data attributes.
   */
  protected static function wrapInContainer($content, $id, $type) {
    $container = [
      '#type' => 'container',
      '#attributes' => [
        'data-layout-editor-object' => $id,
        'data-layout-editor-type' => $type,
      ],
      'element-content' => $content,
    ];
    return $container;
  }

  /**
   * AJAX method: given POST request parameters, return rendered HTML.
   *
   * This method is used solely by the "Add Content" form to populate the
   * preview with content that may be placed into the layout.
   *
   * @param Request $request
   *    This should alway include the nid, entity type, and unique id.
   *
   * @return JSON
   *    A render array.
   */
  public static function get(Request $request) {
    $nid = $request->request->get('nid');
    $type = $request->request->get('type');
    $id = $request->request->get('id');
    if (!isset($nid) || !isset($type) || !isset($id)) {
      throw new NotFoundHttpException();
    }
    // Set 4th parameter to true so we wrap the output in a container that has
    // data attributes that the layout editor JS can find.
    $content = self::buildContent($type, $id, $nid, TRUE);
    $rendered = render($content);
    $response = new Response();
    $response->setContent(json_encode(array('content' => $rendered)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * AJAX method: given POST request parameters, save layout to node.
   *
   * @param Request $request
   *    This should alway include the nid, entity type, and unique id.
   */
  public static function set(Request $request) {
    $layout = array();
    $output = [];
    $nid = $request->request->get('nid');
    $layout_data = $request->request->get('layout');
    $node = Node::load($nid);
    if ($node && !empty($layout_data)) {
      // Prepare data sent via AJAX POST request for storage on the node.
      // The two string_replace functions convert CSS hypens to machine_names.
      foreach ($layout_data as $layout_raw => $values) {
        $layout_id = 'layout_' . str_replace('-', '_', $layout_raw);
        foreach ($values as $region => $contents) {
          $region = str_replace('-', '_', $region);
          foreach ($contents as $id => $type) {
            $output[$layout_id][$region][$id] = $type;
          }
        }
      }
      $existing = $node->get('layout')->getValue();
      if (isset($existing[0][$layout_id])) {
        unset($existing[0][$layout_id]);
      }
      $merged = array_merge($output, $existing[0]);
      $node->layout = $merged;
      $node->setNewRevision();
      $node->setRevisionCreationTime(time());
      $node->setRevisionLogMessage('Layout updated');
      $node->setRevisionTranslationAffected(TRUE);
      $node->save();
    }
    // Return a response regardless of whether we saved or not.
    $response = new Response();
    $response->setContent(json_encode(array('content' => $nid)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  /**
   * Checks access for a specific request.
   */
  public static function eligibleNode() {
    $user = \Drupal::currentUser();
    $allowed = FALSE;
    $query = \Drupal::request()->query->all();
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof ContentEntityInterface) {
      $config = \Drupal::config('layout_per_node.enabled');
      $default_value = $config->get($node->getType());
      if ($default_value == 1 && $user->hasPermission('use layout per node')) {
        $allowed = $node->id();
      }
    }
    return $allowed;
  }

}

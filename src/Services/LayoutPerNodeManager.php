<?php

namespace Drupal\layout_per_node\Services;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\node\Entity\Node;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class LayoutPerNodeManager.
 *
 * @package Drupal\layout_per_node
 */
class LayoutPerNodeManager {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;
  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;
  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactory definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Constructor.
   */
  public function __construct(AccountProxy $current_user, CurrentRouteMatch $current_route_match, ConfigFactory $config_factory, EntityManager $entity_manager, EntityTypeManager $entity_type_manager, BlockManager $plugin_manager_block, LoggerChannelFactory $loggerFactory) {
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManagerBlock = $plugin_manager_block;
    $this->loggerFactory = $loggerFactory;
  }

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
  public function buildContent($type, $id, $nid, $container = FALSE) {
    $content = '';
    switch ($type) {
      case 'field':
        $content = $this->renderableField($nid, $id);
        break;

      case 'block_content':
        $content = $this->renderableBlockContent($id);
        break;

      case 'plugin_block':
        $content = $this->renderablePluginBlock($id);
        break;

    }
    if ($container) {
      $content = $this->wrapInContainer($content, $id, $type);
    }
    return $content;
  }

  /**
   * Main functional method: given a node ID & layout array, save configuration.
   *
   * @param string $nid
   *   The node ID.
   * @param array $updated_layout
   *   The layout array:
   *   Example: Add a block to the 'first' & 'second' region of 'twocol' layout:
   *   $layout_data = [
   *     'twocol' => [
   *       'first' => [
   *         '71393b1f-d47c-49df-9d58-c2343cf0f931' => "block_content",
   *       ],
   *       'second' => [
   *         '2834fc70-714b-4352-9c11-eedf72a1893e' => "block_content",
   *      ]
   *   ];
   *   End notes on layout array.
   *
   * @return bool
   *   Whether the layout was successfully saved or not.
   */
  public function updateLayout($nid, array $updated_layout = []) {
    $node = $this->getCurrentNode($nid);
    if ($existing_layout_entity = $this->getLayoutEntity($node->id(), $node->vid->value)) {
      $existing_layout = $existing_layout_entity->get('layout')->getValue();
      // Assume that we are doing a simple node save from the edit form, and
      // that no updated layout data has been provided as the 3rd argument.
      $layout = $existing_layout;
    }

    // New layout data has been passed in (via LayoutEditorBuilder->set() or
    // via a direct call, e.g., migration).
    if (!empty($updated_layout)) {
      $output = [];
      foreach ($updated_layout as $layout_raw => $values) {
        // This defines which layout ID the regions & fields correspond to.
        $layout_id = 'layout_' . str_replace('-', '_', $layout_raw);
        foreach ($values as $region => $contents) {
          $region = str_replace('-', '_', $region);
          foreach ($contents as $id => $type) {
            $output[$layout_id][$region][$id] = $type;
          }
        }
      }
      if (isset($existing_layout[0][$layout_id])) {
        unset($existing_layout[0][$layout_id]);
        $layout = array_merge($output, $existing_layout[0]);
      }
      else {
        $layout = $output;
      }
      $node->setNewRevision();
      $node->setRevisionCreationTime(time());
      $node->setRevisionLogMessage('Layout updated');
      $node->setRevisionTranslationAffected(TRUE);
      $node->save();
    }

    if ($node) {
      // Save the layout to the layout_per_node_entity.
      $layout_entity = entity_create('layout_per_node_layout', array(
        'entity_id' => $nid,
        'vid' => $node->vid->value,
        'entity_type' => 'node',
        'layout' => $layout,
      ));
      $layout_entity->save();
      return TRUE;
    }
    else {
      \Drupal::logger('layout_per_node')->warning(t("Warning: Attempted to update @nid layout_per_node data and was unable to find existing record", [@nid => $nid]));
      return FALSE;
    }
  }

  /**
   * Simple input-output method for returning a render array for content blocks.
   */
  protected function renderableBlockContent($id) {
    $entity = $this->entityManager->loadEntityByUuid('block_content', $id);
    $block = $this->entityTypeManager->getViewBuilder('block_content')->view($entity);
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
  protected function renderablePluginBlock($id) {
    // @todo: add the ability to pass config directly?
    $config = [];
    $plugin_block = $this->pluginManagerBlock->createInstance($id, $config);
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
  protected function wrapInContainer($content, $id, $type) {
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
   * Simple input-output method for returning a render array for fields.
   */
  protected function renderableField($nid, $field_name) {
    $node = Node::load($nid);
    $entityType = 'node';
    // Before building a renderable object, we need to get the display of this
    // field in the context of the node settings.
    $display = entity_get_display($entityType, $node->getType(), 'default');
    $viewBuilder = $this->entityTypeManager->getViewBuilder($entityType);
    $fieldRenderable = $viewBuilder->viewField($node->{$field_name}, $display->getComponent($field_name));
    return $fieldRenderable;
  }

  /**
   * Checks access for a specific request.
   *
   * @return string
   *   The node id, or 0, if not eligible.
   */
  public function eligibleNode() {
    $allowed = [];
    if ($node = $this->getCurrentNode()) {
      $config = $this->configFactory->get('layout_per_node.enabled');
      $is_enabled = $config->get($node->getType());
      if ($is_enabled && $this->currentUser->hasPermission('use ' . $node->getType() . ' layout per node')) {
        $allowed = $node->id();
      }
    }
    return $allowed;
  }

  /**
   * Helper function to retrieve the node object from the current route.
   *
   * @return obj
   *   The node object, if it exists.
   */
  public function getCurrentNode($nid = 0) {
    if ($nid == 0) {
      // If no node ID is loaded, try to retrieve it from the request.
      // This is used by LayoutEditorBuilder.
      $node = $this->currentRouteMatch->getParameter('node');
    }
    else {
      // A provided URL is used when saving new layout data.
      $node = Node::load($nid);
    }

    // The following if statement is an edge case for the "revisions" view.
    if (is_numeric($node)) {
      $node = Node::load($node);
    }
    return $node;
  }

  /**
   * Helper function to retrieve the layout entity for the latest node revision.
   *
   * @return obj
   *   The node object, if it exists.
   */
  public function getLayoutEntity($nid = 0, $vid = 0) {
    if (!$nid && !$vid) {
      $node = $this->getCurrentNode();
      $entity_id = $node->id();
      $vid = $node->vid->value;
    }

    $query = \Drupal::entityQuery('layout_per_node_layout')
      ->condition('entity_id', $nid)
      ->condition('vid', $vid);
    $ids = $query->execute();
    if (!empty($ids)) {
      if ($layout = entity_load('layout_per_node_layout', key($ids))) {
        return $layout;
      }
    }
    else {
      // New node data was just saved. Get the previous revision's layout.
      $query = \Drupal::entityQuery('layout_per_node_layout')
        ->condition('entity_id', $nid)
        ->condition('vid', $vid - 1);
      $ids = $query->execute();
      if (!empty($ids)) {
        if ($layout = entity_load('layout_per_node_layout', key($ids))) {
          return $layout;
        }
      }
    }
    return FALSE;
  }

}

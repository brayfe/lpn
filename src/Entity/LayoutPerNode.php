<?php

namespace Drupal\layout_per_node\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Layout Per Node entity.
 *
 * @ConfigEntityType(
 *   id = "layout_per_node",
 *   label = @Translation("Layout Per Node"),
 *   handlers = {
 *     "list_builder" = "Drupal\layout_per_node\LayoutPerNodeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\layout_per_node\Form\LayoutPerNodeForm",
 *       "edit" = "Drupal\layout_per_node\Form\LayoutPerNodeForm",
 *       "delete" = "Drupal\layout_per_node\Form\LayoutPerNodeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\layout_per_node\LayoutPerNodeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "layout_per_node",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/layout_per_node/{layout_per_node}",
 *     "add-form" = "/admin/structure/layout_per_node/add",
 *     "edit-form" = "/admin/structure/layout_per_node/{layout_per_node}/edit",
 *     "delete-form" = "/admin/structure/layout_per_node/{layout_per_node}/delete",
 *     "collection" = "/admin/structure/layout_per_node"
 *   }
 * )
 */
class LayoutPerNode extends ConfigEntityBase implements LayoutPerNodeInterface {

  /**
   * The Layout Per Node ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Layout Per Node label.
   *
   * @var string
   */
  protected $label;

}

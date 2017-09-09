<?php

namespace Drupal\layout_per_node\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the Layout entity.
 *
 * @ingroup layout_per_node
 *
 * @ContentEntityType(
 *   id = "layout_per_node_layout",
 *   label = @Translation("Layout"),
 *   base_table = "layout_per_node",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Layout extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Layout entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Layout entity.'))
      ->setReadOnly(TRUE);

    // Currently, this will ALWAYS be "node". Included to not preclude allowing
    // other entity types in the future.
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type of the item.'))
      ->setReadOnly(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity id.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('VID'))
      ->setDescription(t('The revision id.'))
      ->setReadOnly(TRUE);

    $fields['layout'] = BaseFieldDefinition::create('map')
      ->setLabel(t('UUID'))
      ->setDescription(t('The layout itself.'))
      ->setReadOnly(TRUE);
    return $fields;
  }

}

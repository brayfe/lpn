<?php
/**
 * @file
 * Layout Per Node Manager class definition.
 */

namespace Drupal\layout_per_node;

use Drupal\node\Entity\Node;

class LayoutPerNodeManager {
  /**
   * @param string $nid
   * @param array $layout_data
   * This should be an array with layout information.
   * Example: Add a block to the 'first' and 'second' region of 'twocol' layout:
   * $layout_data = [
   *   'twocol' => [
   *     'first' => [
   *       '71393b1f-d47c-49df-9d58-c2343cf0f931' => "block_content",
   *     ],
   *     'second' => [
   *       '2834fc70-714b-4352-9c11-eedf72a1893e' => "block_content",
   *    ]
   * ];
   * @return bool
   */
  public static function updateContent($nid, $layout_data) {
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
      return $node;
    }
    else {
      \Drupal::logger('layout_per_node')->warning(t("Warning: Attempted to update @nid layout_per_node data and was unable to find existing record", [@nid => $nid]));
      return FALSE;
    }
  }
}
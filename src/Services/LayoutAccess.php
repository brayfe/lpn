<?php

namespace Drupal\layout_per_node\Services;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Provides an access checker for node revisions.
 *
 * @ingroup node_access
 */
class LayoutAccess implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account, Request $request) {
    $is_enabled = FALSE;
    $id = $request->get('id');

    if (isset($id)) {
      $node = Node::load($id);
      $config = \Drupal::config('layout_per_node.content_type.' . $node->getType());
      $is_enabled = $config->get('enabled');
      return AccessResult::allowedIf($is_enabled && $account->hasPermission('use ' . $node->getType() . ' layout per node'));
    }
    return AccessResult::allowedIf(FALSE);
  }

}

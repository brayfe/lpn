<?php

namespace Drupal\layout_per_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_per_node\LayoutPerNodeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LayoutEditorController.
 *
 * AJAX get() and set() methods for handling content.
 */
class LayoutEditorController extends ControllerBase {

  /**
   * The instantiated LayoutPerNodeManager class.
   *
   * @var obj
   */
  private $layoutPerNodeManager;

  /**
   * The class constructor.
   */
  public function __construct($layout_per_node_service) {
    $this->layoutPerNodeManager = $layout_per_node_service;
  }

  /**
   * The create method.
   *
   * @param ContainerInterface $container
   *   The Drupal container interface.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_per_node.manager')
    );
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
  public function get(Request $request) {
    $nid = $request->request->get('nid');
    $type = $request->request->get('type');
    $id = $request->request->get('id');
    if (!isset($nid) || !isset($type) || !isset($id)) {
      throw new NotFoundHttpException();
    }
    // Set 4th parameter to true so we wrap the output in a container that has
    // data attributes that the layout editor JS can find.
    $content = $this->layoutPerNodeManager->buildContent($type, $id, $nid, TRUE);
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
   *    This should always include the nid, entity type, and unique id.
   */
  public function set(Request $request) {
    $layout = array();
    $output = [];
    $nid = $request->request->get('nid');
    $layout_data = $request->request->get('layout');
    $this->layoutPerNodeManager->updateLayout($nid, $layout_data);
    // Return a response regardless of whether we saved or not.
    $response = new Response();
    $response->setContent(json_encode(array('content' => $nid)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}

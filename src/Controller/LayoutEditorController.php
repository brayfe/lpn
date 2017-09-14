<?php

namespace Drupal\layout_per_node\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\layout_per_node\LayoutPerNodeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   *    This should always include the nid, entity type, and unique id.
   *
   * @return JSON
   *    A render array.
   */
  public function get(Request $request) {
    $id = $request->request->get('id');
    $type = $request->request->get('type');
    $container = $request->request->get('container');
    if (!isset($id) || !isset($type) || !isset($container)) {
      throw new NotFoundHttpException();
    }
    // Set 4th parameter to true so we wrap the output in a container that has
    // data attributes that the layout editor JS can find.
    $content = $this->layoutPerNodeManager->buildContent($type, $container, $id, TRUE);
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
    $id = $request->request->get('id');
    $layout_data = $request->request->get('layout');
    if (!isset($layout_data) || !isset($id)) {
      throw new NotFoundHttpException();
    }
    $this->layoutPerNodeManager->updateLayout($id, $layout_data);
    // Return a response regardless of whether we saved or not.
    $response = new Response();
    $response->setContent(json_encode(array('content' => $id)));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

}

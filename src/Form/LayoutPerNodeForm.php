<?php

namespace Drupal\layout_per_node\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LayoutPerNodeForm.
 *
 * @package Drupal\layout_per_node\Form
 */
class LayoutPerNodeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $layout_per_node = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $layout_per_node->label(),
      '#description' => $this->t("Label for the Layout Per Node."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $layout_per_node->id(),
      '#machine_name' => [
        'exists' => '\Drupal\layout_per_node\Entity\LayoutPerNode::load',
      ],
      '#disabled' => !$layout_per_node->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $layout_per_node = $this->entity;
    $status = $layout_per_node->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Layout Per Node.', [
          '%label' => $layout_per_node->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Layout Per Node.', [
          '%label' => $layout_per_node->label(),
        ]));
    }
    $form_state->setRedirectUrl($layout_per_node->urlInfo('collection'));
  }

}

<?php

namespace Drupal\tide_site_restriction\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Plugin\views\field\NodeBulkForm;

/**
 * {@inheritdoc}
 */
class TideNodeBulkForm extends NodeBulkForm {

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());
    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntity($row);
        if ($entity !== NULL) {
          $entity = $this->getEntityTranslationByRelationship($entity, $row);

          $form[$this->options['id']][$row_index] = [
            '#type' => 'checkbox',
            // We are not able to determine a main "title" for each row, so we
            // can only output a generic label.
            '#title' => $this->t('Update this item'),
            '#title_display' => 'invisible',
            '#disabled' => !\Drupal::currentUser()->hasPermission('tide node bulk update') && !\Drupal::service('tide_site_restriction.helper')->canBypassRestriction(\Drupal::currentUser()) ,
            '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
            '#return_value' => $this->calculateEntityBulkFormKey($entity, $use_revision),
          ];
        }
        else {
          $form[$this->options['id']][$row_index] = [];
        }

      }

      // Replace the form submit button label.
      $form['actions']['submit']['#value'] = $this->t('Apply to selected items');

      // Ensure a consistent container for filters/operations
      // in the view header.
      $form['header'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = [
        '#type' => 'container',
      ];
      $form['header'][$this->options['id']]['action'] = [
        '#type' => 'select',
        '#title' => $this->options['action_title'],
        '#options' => $this->getBulkOptions(),
        '#empty_option' => $this->t('- Select -'),
      ];
      $current_user = \Drupal::currentUser();
      if (!$current_user->hasPermission('tide node bulk update') &&
          !\Drupal::service('tide_site_restriction.helper')->canBypassRestriction($current_user)) {
        $form['actions']['submit']['#disabled'] = TRUE;
        $form['header'][$this->options['id']]['action']['#disabled'] = TRUE;
      }
      // Duplicate the form actions into the action container in the header.
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }
  }

}

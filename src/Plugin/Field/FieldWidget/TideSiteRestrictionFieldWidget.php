<?php

namespace Drupal\tide_site_restriction\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\tide_site\TideSiteHelper;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'tide_site_restriction_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "tide_site_restriction_field_widget",
 *   label = @Translation("Tide site restriction"),
 *   description = @Translation("Site selector widget."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class TideSiteRestrictionFieldWidget extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  /**
   * Current User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Tide Site Restriction helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * Tide site helper.
   *
   * @var \Drupal\tide_site\TideSiteHelper
   */
  protected $tideSiteHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountProxyInterface $currentUser, Helper $helper, TideSiteHelper $tideSiteHelper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $currentUser;
    $this->helper = $helper;
    $this->tideSiteHelper = $tideSiteHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('tide_site_restriction.helper'),
      $container->get('tide_site.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $userSites = $this->helper->getUserSites(User::load($this->currentUser->id()));
    if ($userSites) {
      $unavailableSites = array_diff_key($element['#options'], $userSites);
      foreach ($unavailableSites as $key => $item) {
        $element[$key] = ['#disabled' => TRUE];
      }
    }
    if (!is_array($element['#default_value'])) {
      $element['#default_value'] = $element['#default_value'] ? $element['#default_value'] : key($element['#options']);
    }
    $element['#default_value'] = empty($element['#default_value']) ? array_keys($element['#options']) : $element['#default_value'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    $selected = [];
    if ($entity instanceof NodeInterface) {
      $field_name = $this->fieldDefinition->getName();
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $values = $entity->get($field_name)->getValue();
        $selected = array_column($values, 'target_id');
      }
    }
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $options;
    }

    return $this->userOptionsFilter($this->currentUser, $options, $selected);
  }

  /**
   * Filters options based on user's permissions.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user.
   * @param array $options
   *   The default options.
   * @param array $selected
   *   The selected options.
   *
   * @return array
   *   The options.
   */
  protected function userOptionsFilter(AccountProxyInterface $account, array $options, array $selected) {
    $allSites = array_merge($this->helper->getUserSitesTrail(User::load($account->id())), $selected);
    $options = array_intersect_key($options, array_flip($allSites));
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $results = parent::massageFormValues($values, $form, $form_state);
    $options = [];
    $node = $form_state->getFormObject()->getEntity();
    if (!$this->helper->canBypassRestriction($this->currentUser)) {
      if (!$node->isNew() && ($this->fieldDefinition->getName() != 'field_node_primary_site')) {
        $last_revision = $this->helper->getLastNodeRevision($node);
        $revision_value = $last_revision->get($this->fieldDefinition->getName())->getValue();
        $user_sites = $this->helper->getUserSites(User::load($this->currentUser->id()));
        $diff = array_diff(array_column($revision_value, 'target_id'), $user_sites);
        $results = array_unique(array_merge(array_column($results, 'target_id'), $diff));
        $chunks = array_chunk($results, 1);
        $key = ['target_id'];
        // Reassemble the results array.
        $results = array_map(function ($chunk) use ($key) {
          return array_combine($key, $chunk);
        }, $chunks);
      }
    }
    // Get Parent Ids.
    foreach ($results as $result) {
      $parents = $this->tideSiteHelper->getSiteTrail($result['target_id']);
      $parentSiteId = reset($parents);
      if ($parentSiteId == $result['target_id']) {
        continue;
      }
      $options[] = ['target_id' => $parentSiteId];
    }
    return array_map('unserialize', array_unique(array_map('serialize', array_merge($results, $options))));
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return (($field_definition->getName() == 'field_node_primary_site' || $field_definition->getName() == 'field_node_site') && $field_definition->getTargetEntityTypeId() == 'node');
  }

}

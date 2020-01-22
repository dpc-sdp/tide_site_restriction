<?php

namespace Drupal\tide_site_restriction\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $options;
    }
    return $this->userOptionsFilter($this->currentUser, $options);
  }

  /**
   * Filters options based on user's permissions.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The user.
   * @param array $options
   *   The default options.
   *
   * @return array
   *   The options.
   */
  protected function userOptionsFilter(AccountProxyInterface $account, array $options) {
    $userSites = $this->helper->getUserSites(User::load($account->id()));
    $result = [];
    foreach ($userSites as $site) {
      $trail = $this->helper->getSiteTrail($site);
      $parent_id = reset($trail);
      $result[] = $parent_id;
    }
    $allSites = array_merge(array_unique($result), $userSites);
    $options = array_intersect_key($options, array_flip($allSites));
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $results = parent::massageFormValues($values, $form, $form_state);
    $options = [];
    foreach ($results as $result) {
      $parents = $this->tideSiteHelper->getSiteTrail($result['target_id']);
      $parentSiteId = reset($parents);
      if ($parentSiteId == $result['target_id']) {
        continue;
      }
      $options[$parentSiteId] = ['target_id' => $parentSiteId];
    }
    return array_map('unserialize', array_unique(array_map('serialize', array_merge($results, $options))));
  }

}

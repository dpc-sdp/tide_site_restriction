<?php

namespace Drupal\tide_site_restriction\Plugin\Field\FieldWidget;

use Drupal\content_moderation\ModerationInformation;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tide_site\TideSiteFields;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * ModerationInformation helper class.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, Helper $helper, ModerationInformation $moderation_information) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->helper = $helper;
    $this->moderationInformation = $moderation_information;
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
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('tide_site_restriction.helper'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $current_user = User::load($this->currentUser->id());
    $userSites = $this->helper->getUserSites($current_user);
    if ($userSites && !$this->helper->canBypassRestriction($current_user)) {
      $unavailableSites = array_diff_key($element['#options'], $userSites);
      foreach ($unavailableSites as $key => $item) {
        $element[$key] = ['#disabled' => TRUE];
      }
    }
    if ($this->multiple) {
      $element['#default_value'] = empty($element['#default_value']) ? !$this->helper->canBypassRestriction($current_user) ? array_keys($element['#options']) : [] : $element['#default_value'];
    }
    else {
      $element['#default_value'] = $element['#default_value'] ? $element['#default_value'] : key($element['#options']);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    $selected = [];
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $options;
    }
    $field_name = $this->fieldDefinition->getName();
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      $values = $entity->get($field_name)->getValue();
      $selected = array_column($values, 'target_id');
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
    // If the user could bypass the restrictions, returns results directly.
    if ($this->helper->canBypassRestriction($this->currentUser)) {
      return $results;
    }
    // If the form object was not an entity form, eg, entity browser form,
    // Returns results directly.
    $options = [];
    if ($form_state->getFormObject() instanceof EntityFormInterface) {
      $entity = $form_state->getFormObject()->getEntity();
      // Calculates the results if the user could not bypass the restrictions
      // and the FormObject was entity form.
      if (!$entity->isNew() && $this->multiple) {
        /** @var \Drupal\Core\Entity\EntityStorageInterface|\Drupal\Core\Entity\RevisionableStorageInterface $entityStorage */
        $entityStorage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        $latestRevisionId = $entityStorage->getLatestRevisionId($entity->id());
        if ($latestRevisionId) {
          /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\Core\Entity\RevisionableInterface $latest */
          $last_revision = $entityStorage->loadRevision($latestRevisionId);
        }
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
      $parents = $this->helper->getSiteTrail($result['target_id']);
      $parentSiteId = reset($parents);
      if ($parentSiteId == $result['target_id']) {
        continue;
      }
      $options[] = ['target_id' => $parentSiteId];
    }
    return array_unique(array_merge($results, $options), SORT_REGULAR);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TideSiteFields::isSiteField($field_definition->getName());
  }

}

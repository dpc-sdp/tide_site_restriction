<?php

namespace Drupal\tide_site_restriction\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\entity_clone\Event\EntityCloneEvent;
use Drupal\node\NodeInterface;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TideSiteRestrictionEntityClone.
 */
class TideSiteRestrictionEntityClone implements EventSubscriberInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The tide restriction helper class.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * The term entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   */
  protected $termStorage;

  /**
   * Constructs a TideSiteRestrictionEntityClone class.
   */
  public function __construct(AccountProxy $currentUser, Helper $helper, EntityTypeManager $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->helper = $helper;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity_clone.post_clone' => 'postCloneUpdate',
    ];
  }

  /**
   * Sets default values to site fields.
   */
  public function postCloneUpdate(EntityCloneEvent $event) {
    $cloned_entity = $event->getClonedEntity();
    if ($cloned_entity instanceof NodeInterface) {
      if (!$this->helper->canBypassRestriction($this->currentUser)) {
        $sites = $this->helper->getUserSites(User::load($this->currentUser->id()));
        $random_site = reset($sites);
        $term = $this->termStorage->loadParents($random_site);
        if (empty($term)) {
          $primary_site = $random_site;
        }
        else {
          $primary_site = $term->id();
        }
        $cloned_entity->field_node_primary_site->target_id = $primary_site;
        $cloned_entity->set('field_node_site', []);
        foreach ($sites as $site) {
          $cloned_entity->field_node_site[] = $site;
        }
      }
      $cloned_entity->save();
    }
  }

}

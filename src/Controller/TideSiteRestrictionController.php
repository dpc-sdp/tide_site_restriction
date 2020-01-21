<?php

namespace Drupal\tide_site_restriction\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\tide_site_restriction\Helper;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller which serves to the tide_site_restriction module.
 */
class TideSiteRestrictionController extends ControllerBase {

  /**
   * Tide site restriction helper.
   *
   * @var \Drupal\tide_site_restriction\Helper
   */
  protected $helper;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $render;

  /**
   * {@inheritdoc}
   */
  public function __construct(Helper $helper, EntityTypeManagerInterface $entityTypeManager, RendererInterface $render) {
    $this->helper = $helper;
    $this->entityTypeManager = $entityTypeManager;
    $this->render = $render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tide_site_restriction.helper'),
      $container->get('entity_type.manager'),
      $container->get('renderer'));
  }

  /**
   * Renders render array to tide_site_restriction.user_tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user in the router.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function sitesAllocationTab(UserInterface $user) {
    // Gets the display and the view builder objects.
    $display = $this->entityTypeManager->getStorage('entity_view_display')->load('user.user.default');
    $viewBuilder = $this->entityTypeManager->getViewBuilder('user');
    $fieldRenderable = $viewBuilder->viewField($user->field_user_site, $display->getComponent('field_user_site'));
    $markup[] = $this->render->renderRoot($fieldRenderable);
    if (count($markup) && !in_array("", $markup, TRUE)) {
      $build = [
        '#type' => 'markup',
        '#markup' => implode("", $markup),
      ];
    }
    else {
      $build = ['#markup' => t('No sites assigned.')];
    }
    $build['#cache'] = [
      'tags' => ['user:' . $user->id()],
    ];
    return $build;
  }

}

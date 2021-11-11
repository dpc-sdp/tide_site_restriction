<?php

namespace Drupal\tide_site_restriction\Plugin\views\filter;

use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\ManyToOne;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters by sub-sites.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("sub_sites_filter")
 */
class SubSitesFilter extends ManyToOne {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a SubSitesFilter object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\taxonomy\TermStorageInterface $term_storage
   *   The term storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TermStorageInterface $term_storage, AccountInterface $current_user = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->termStorage = $term_storage;
    $current_user = \Drupal::service('current_user');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Filter by sites');
    $this->definition['options callback'] = [$this, 'generateOptions'];
    $this->currentDisplay = $view->current_display;
  }

  /**
   * Generates the options.
   *
   * @return array
   *   An array of tid and its label.
   */
  public function generateOptions() {
    $tree = $this->termStorage->loadTree('sites', 0, NULL, TRUE);
    $options = [];
    if ($tree) {
      foreach ($tree as $term) {
        if (!$term->isPublished() && !$this->currentUser->hasPermission('administer taxonomy')) {
          continue;
        }
        $choice = new \stdClass();
        $choice->option = [$term->id() => str_repeat('-', $term->depth) . \Drupal::service('entity.repository')->getTranslationFromContext($term)->label()];
        $options[] = $choice;
      }
    }
    return $options;
  }

  /**
   * Builds the query.
   */
  public function query() {
    if (!empty($this->value)) {
      $configuration = [
        'table' => 'node__field_node_site',
        'type' => 'INNER',
        'field' => 'entity_id',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'operator' => '=',
      ];
      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $this->query->addRelationship('node__field_node_site', $join, 'node_field_data');
      $this->query->addWhere('AND', 'node__field_node_site.field_node_site_target_id', $this->value, 'IN');
    }
  }

}

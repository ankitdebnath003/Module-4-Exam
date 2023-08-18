<?php

namespace Drupal\related_blogs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a related blogs block.
 *
 * @Block(
 *   id = "related_blogs",
 *   admin_label = @Translation("Related Blogs"),
 *   category = @Translation("Blogs"),
 * )
 */
class RelatedBlogsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the instance of Route Match Interface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Stores the instance of Entity Type Manager Interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initializes the instance of the block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Stores the instance of Route Match Interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Stores the instance of Entity Type Manager Interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Getting the node details from the url.
    $node = $this->routeMatch->getParameter('node');
    $uid = $node->getOwnerId();
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['uid' => $uid]);

    // Remove the current node id and fetch the likes of the remaining nodes.
    unset($nodes[$node->id()]);
    if (!$nodes) {
      return [
        '#markup' => 'No Related Nodes',
        '#cache' => [
          'tags' => ['related_blogs_tag'],
        ],
      ];
    }
    $like_count = [];
    foreach ($nodes as $key => $n) {
      $like = $n->field_like->getValue()[0]['likes'];
      if ($like) {
        $like_count[$key] = $like;
      }
    }

    // Sorting the likes by its value.
    arsort($like_count);

    // Getting only the first 3 nodes details.
    $count = 0;
    foreach ($like_count as $key => $value) {
      $node = $this->entityTypeManager->getStorage('node')->load($key);
      $node_names[] = [
        'link' => '/blogs/' . $key,
        'name' => $node->title->value,
      ];
      if ($count == 3) {
        break;
      }
      $count++;
    }
    // Building the block to show the nodes with highest likes and of the same
    // author.
    foreach ($node_names as $nodes) {
      $build['content'][] = [
        '#markup' => "<a href = " . $nodes['link'] . "> " . $nodes['name'] . "</a><br>",
      ];
    }
    $build['content'][] = [
      '#cache' => [
        'tags' => ['related_blogs_tag'],
      ],
    ];
    return $build;
  }

}

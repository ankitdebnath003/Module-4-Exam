<?php

namespace Drupal\backend_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\mysql\Driver\Database\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class is used to create API of Blogs Nodes.
 *
 * It creates API of blogs nodes based on the author, tag name, date range if
 * specified in the Url otherwise show API of all Blogs nodes' data.
 *
 * @package Drupal\backend_api\Controller
 */
class ApiController extends ControllerBase {

  /**
   * Stores the instance of Entity Type Manager Interface.
   *
   * @var string
   */
  protected const AUTHOR_ERROR = 'Invalid Author Name';

  /**
   * Stores the instance of Entity Type Manager Interface.
   *
   * @var string
   */
  protected const TAG_ERROR = 'Invalid Tag Name';

  /**
   * Stores the instance of Entity Type Manager Interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Stores the object of Connection class.
   *
   * @var \Drupal\mysql\Driver\Database\mysql\Connection
   */
  protected $connection;

  /**
   * Initializes the object to class variables.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Stores the instance of Entity Type Manager Interface.
   * @param \Drupal\mysql\Driver\Database\mysql\Connection $connection
   *   Stores the object of Connection class.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * This function is used to get the Blogs node details.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns the API of Blogs nodes data.
   */
  public function getBlogNodes(Request $request) {
    $author = $request->get('author');
    $tag = $request->get('tag');
    $date = $request->get('date');

    $node_details['title'] = 'Blogs Nodes';
    $node_details['data'] = [];

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'blogs')
      ->accessCheck(FALSE);

    // Checking if author has provided in the url. Then get the nodes of the
    // author or authors.
    if ($author) {
      $author = explode(',', $author);
      $user = $this->entityTypeManager->getStorage('user')->getQuery()
        ->condition('name', $author, 'IN')
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->execute();

      if (!$user) {
        return new JsonResponse(['error' => self::AUTHOR_ERROR], 401);
      }
      $query->condition('uid', $user, 'IN');
    }

    // Checking if tag has provided in the url. Then get the nodes of the tag
    // or tags.
    if ($tag) {
      $tag = explode(',', $tag);
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $tag]);
      $term_id = [];
      foreach ($term as $id => $value) {
        $term_id[] = $id;
      }
      if (!$term_id) {
        return new JsonResponse(['error' => self::TAG_ERROR], 401);
      }
      $query->condition('field_blogs_tags', $term_id, 'IN');
    }

    // Checking if date has provided in the url. Then get the nodes between the
    // date range.
    if ($date) {
      $date = explode(',', $date);
      $start = $date[0];
      $end = $date[1];
      $query->condition('field_published_date', $start, '>=');
      $query->condition('field_published_date', $end, '<=');
    }

    // Getting the node ids.
    $results = $query->execute();

    // Getting all the node details.
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($results);
    foreach ($nodes as $node) {
      $node_details['data'][] = $this->getApiDetails($node);
    }

    // If no nodes found then the message will be shown.
    if (!$node_details['data']) {
      $node_details['data'] = 'No Nodes Found';
    }

    return new JsonResponse($node_details);
  }

  /**
   * This function is used to get all the details needed in the API.
   *
   * @param object $node
   *   Stores the object of node.
   *
   * @return array
   *   Returns the details of the node.
   */
  public function getApiDetails(object $node) {
    return [
      'title' => $node->getTitle(),
      'body' => $this->getBody($node),
      'Published Date' => $this->getPublishedDate($node),
      'Author' => $this->getAuthorName($node->getOwnerId()),
      'Tags' => $this->getTagsNames($node->field_blogs_tags->getValue()),
    ];
  }

  /**
   * This function is used to get the body of the node.
   *
   * @param object $node
   *   Stores the object of the node.
   *
   * @return array
   *   Returns the body of the node.
   */
  public function getBody(object $node) {
    return [
      'value' => $node->body->value,
      'format' => $node->body->format,
      'summary' => $node->body->summary,
    ];
  }

  /**
   * This function is used to get the published date of the node.
   *
   * @param object $node
   *   Stores the object of the node.
   *
   * @return string
   *   Returns the published date of the node.
   */
  public function getPublishedDate(object $node) {
    return $node->field_published_date->value;
  }

  /**
   * This function is used to get the Author name of the node.
   *
   * @param int $uid
   *   Stores the user's id who created the node.
   *
   * @return string
   *   Returns the user's name.
   */
  public function getAuthorName(int $uid) {
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    return $user->name->value;
  }

  /**
   * This function is used to get the Taxonomy term names of the node.
   *
   * @param array $terms
   *   Stores the node's id.
   *
   * @return string
   *   Returns the terms' names separated by comma.
   */
  public function getTagsNames(array $terms) {
    // Getting the taxonomy term names.
    foreach ($terms as $term) {
      $term_details = $this->entityTypeManager->getStorage('taxonomy_term')->load($term['target_id']);
      $term_names[] = $term_details->label();
    }
    return implode(', ', $term_names);
  }

}

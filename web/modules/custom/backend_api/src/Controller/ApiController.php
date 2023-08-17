<?php

namespace Drupal\backend_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\mysql\Driver\Database\mysql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

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
  public function getBlogNodes() {
    $node_details['title'] = 'Blogs Nodes';
    $node_details['data'] = [];

    // Getting all the nodes of Blogs type.
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'blogs']);
    foreach ($nodes as $node) {
      $node_details['data'][] = $this->getApiDetails($node);
    }

    return new JsonResponse($node_details);
  }

  /**
   * This function is used to get the nodes of the date range given in the url.
   *
   * Sorting the Blogs nodes and getting only the nodes which one is between the
   * start and end date range.
   *
   * @param string $start
   *   Stores the start date given in the url.
   * @param string $end
   *   Stores the end date given in the url.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns the API of Blogs node data.
   */
  public function getBlogNodesByDate(string $start, string $end) {
    // Fetching the node ids from database that is between the given date range.
    $nodes = $this->connection->select('node__field_published_date', 'n')
      ->fields('n', ['entity_id'])
      ->condition('field_published_date_value', $start, '>=')
      ->condition('field_published_date_value', $end, '<=')
      ->execute()
      ->fetchAll();

    if (!$nodes) {
      return new JsonResponse(['data' => 'No Nodes Found In This Date Range'], 401);
    }

    // Getting all the details of the API.
    $node_details['title'] = 'Blogs Nodes From ' . $start . ' To ' . $end;
    $node_details['data'] = [];
    foreach ($nodes as $node) {
      $node_detail = $this->entityTypeManager->getStorage('node')->load($node->entity_id);
      $node_details['data'][] = $this->getApiDetails($node_detail);
    }
    return new JsonResponse($node_details);
  }

  /**
   * This function is used to get the node details of the user given in the url.
   *
   * Sorting the Blogs nodes and getting only the nodes which one this user has
   * created and then getting the data of the nodes.
   *
   * @param string $author
   *   Stores the author name given in the url.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns the API of Blogs node data.
   */
  public function getBlogNodesByAuthor(string $author) {
    $user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $author]);
    if (!$user) {
      return new JsonResponse(['error' => 'Username Not Found'], 401);
    }
    foreach ($user as $id => $user_details) {
      $uid = $id;
      $name = $user_details->name->value;
    }

    // Getting all the node ids.
    $nodes = $this->connection->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('uid', $uid)
      ->execute()
      ->fetchAll();

    if (!$nodes) {
      return new JsonResponse(['data' => 'No Nodes Found Of This User'], 401);
    }

    // Getting all the details of the API.
    $node_details['title'] = 'Blogs Nodes of Author : ' . $name;
    $node_details['data'] = [];
    foreach ($nodes as $node) {
      $node_detail = $this->entityTypeManager->getStorage('node')->load($node->nid);
      $node_details['data'][] = $this->getApiDetails($node_detail);
    }
    return new JsonResponse($node_details);
  }

  /**
   * This function is used to get the node details of the tag given in the url.
   *
   * Sorting the Blogs nodes and getting only the nodes which one uses the given
   * tag.
   *
   * @param string $tag
   *   Stores the tag name given in the url.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns the API of Blogs node data.
   */
  public function getBlogNodesByTag(string $tag) {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $tag]);
    if (!$term) {
      return new JsonResponse(['error' => 'Term Not Found'], 401);
    }

    // Getting the term name and id.
    foreach ($term as $id => $term_details) {
      $tid = $id;
      $name = $term_details->name->value;
    }

    // Getting all the node ids.
    $nodes = $this->connection->select('taxonomy_index', 'n')
      ->fields('n', ['nid'])
      ->condition('tid', $tid)
      ->execute()
      ->fetchAll();

    if (!$nodes) {
      return new JsonResponse(['data' => 'No Nodes Found Of This Tag'], 401);
    }

    // Getting all the details of the API.
    $node_details['title'] = 'Blogs Nodes of Tag : ' . $name;
    $node_details['data'] = [];
    foreach ($nodes as $node) {
      $node_detail = $this->entityTypeManager->getStorage('node')->load($node->nid);
      $node_details['data'][] = $this->getApiDetails($node_detail);
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
      'title' => $this->getTitle($node),
      'body' => $this->getBody($node),
      'Published Date' => $this->getPublishedDate($node),
      'Author' => $this->getAuthorName($node->getOwnerId()),
      'Tags' => $this->getTagsNames($node->id()),
    ];
  }

  /**
   * This function is used to get the title of the node.
   *
   * @param object $node
   *   Stores the object of the node.
   *
   * @return string
   *   Returns the title of the node.
   */
  public function getTitle(object $node) {
    return $node->title->value;
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
   * @param int $nid
   *   Stores the node's id.
   *
   * @return string
   *   Returns the terms' names separated by comma.
   */
  public function getTagsNames(int $nid) {
    // Getting all the term id's that are associated with the node id.
    $terms = $this->connection->select('taxonomy_index', 'n')
      ->fields('n', ['tid'])
      ->condition('nid', $nid)
      ->execute()
      ->fetchAll();

    // Getting the taxonomy term names.
    foreach ($terms as $term) {
      $term_details = $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid);
      $term_names[] = $term_details->name->value;
    }
    return implode(', ', $term_names);
  }

}

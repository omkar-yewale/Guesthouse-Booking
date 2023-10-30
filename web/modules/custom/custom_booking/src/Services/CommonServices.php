<?php

namespace Drupal\custom_booking\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a common service to fetch node titles based on content type.
 */
class CommonServices {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CommonServices object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Fetches the node title based on content type.
   *
   * @param string $contentType
   *   The machine name of the content type.
   *
   * @return string|null
   *   The node title if found, or NULL if not found.
   */
  public function getNodeTitleByContentType($contentType) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery()
      ->condition('type', $contentType)
      ->accessCheck(FALSE)
      ->range(0, 1);
    $nids = $query->execute();
    if (!empty($nids)) {
      $node = $nodeStorage->load(reset($nids));
      return $node->label();
    }
    return NULL;
  }

}

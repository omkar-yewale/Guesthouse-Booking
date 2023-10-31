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
   * Get webform submissions ids.
   */
  public function getBookedNids($startDate, $endDate, $roomId, $guesthouse) {
    $query = \Drupal::service('webform_query');
    $query->addCondition('guesthouse', $guesthouse);
    $query->addCondition('assign_room', $roomId);
    $query->addCondition('start_date', $endDate, '<=');
    $query->addCondition('end_date', $startDate, '>=');
    $bookedRoomNids = $query->processQuery()->fetchCol();
    return $bookedRoomNids;
  }

  /**
   * Calculate Total number of days.
   */
  public function getTotalDays($startDate, $endDate) {
    $startDateObj = new \DateTime($startDate);
    $endDateObj = new \DateTime($endDate);
    $interval = $startDateObj->diff($endDateObj);
    $numberOfDays = $interval->days + 1;
    return $numberOfDays;
  }

  /**
   * Calculate Total charges of booking.
   */
  public function totalCostCalculation($totalDays, $roomType) {
    $totalCost = 0;
    $config = \Drupal::config('custom_booking.settings');
    if (!empty($roomType) && $roomType == 'shared') {
      $sharedRoomCost = $config->get('shared_room_cost');
      $totalCost = $totalDays * $sharedRoomCost;
    }
    if (!empty($roomType) && $roomType == 'single') {
      $singleRoomCost = $config->get('single_room_cost');
      $totalCost = $totalDays * $singleRoomCost;
    }

    return $totalCost;
  }

}

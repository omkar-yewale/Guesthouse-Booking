<?php

namespace Drupal\custom_booking\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webform\Entity\WebformSubmission;

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

  /**
   * Get room list options value.
   */
  public function getRoomListOptions($guesthouseId, $startDate, $endDate) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $query = $nodeStorage->getQuery()
      ->condition('type', 'rooms')
      ->condition('field_guesthouse', $guesthouseId)
      ->accessCheck(TRUE);
    $subquery = \Drupal::service('webform_query');
    $subquery->addCondition('guesthouse', $guesthouseId);
    $subquery->addCondition('start_date', $endDate, '<=');
    $subquery->addCondition('end_date', $startDate, '>=');
    $bookedRoomNids = $subquery->processQuery()->fetchCol();
    $submissionId = $this->getSubmissionId();
    // For edit form confition.
    if (!empty($submissionId) || $submissionId != '') {
      if (in_array($submissionId, $bookedRoomNids)) {
        $bookedRoomNids = array_diff($bookedRoomNids, [$submissionId]);
      }
    }
    foreach ($bookedRoomNids as $bookedRoomNid) {
      $submission = WebformSubmission::load($bookedRoomNid);
      if ($submission) {
        $occupancy = $submission->getElementData('room_occupancy');
        $room = $submission->getElementData('assign_room');
        if ($occupancy == 'single') {
          $arr[] = $room;
        }
        else {
          // Load room capacity.
          $roomCapacity = $this->getRoomCapacity($room);
          $bookingIds = $this->getBookedNids($startDate, $endDate, $room, $guesthouseId);
          if (count($bookingIds) >= $roomCapacity) {
            $arr[] = $room;
          }
        }
      }
    }
    if (!empty($arr)) {
      $query->condition('nid', $arr, 'NOT IN');
    }
    $roomNids = $query->execute();
    if (!empty($roomNids)) {
      // $options = ['selected' => '- Select a value -'];
      $room_storage = $this->entityTypeManager->getStorage('node');
      $options = [];
      foreach ($roomNids as $nid) {
        $room = $room_storage->load($nid);
        if ($room) {
          $options[$room->id()] = $room->label();
        }
      }
    }
    return $options;
  }

  /**
   * Get room Capacity value.
   */
  public function getRoomCapacity($roomId) {
    $node = $this->entityTypeManager->getStorage('node')->load($roomId);
    $capacity = $node->field_room_capacity->value;

    return $capacity;
  }

  /**
   * Get webform submission Id.
   */
  public function getSubmissionId() {
    // Get the submission ID from the route parameters.
    $route_match = \Drupal::routeMatch();
    $submissionId = $route_match->getParameter('webform_submission');
    if ($submissionId instanceof WebformSubmission) {
      $submissionId = $submissionId->id();
    }

    return $submissionId;
  }

}

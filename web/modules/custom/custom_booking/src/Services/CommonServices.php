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
   * Get room Capacity value.
   */
  public function getName($id, $type) {
    if ($type == 'user') {
      $load = $this->entityTypeManager->getStorage($type)->load($id);
    }
    elseif ($type == 'node') {
      $load = $this->entityTypeManager->getStorage($type)->load($id);
      $name = $load->title->value;
      return $name;
    }
    else {
      $load = $this->entityTypeManager->getStorage($type)->load($id);
    }
    $name = $load->get('name')->value ?? '-';

    return $name;
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

  /**
   * Monthly booking report send on email.
   */
  public function bookingReport() {
    $firstDayofLastMonth = new \DateTime('first day of last month');
    $lastDayofLastMonth = new \DateTime('last day of last month');
    $subquery = \Drupal::service('webform_query');
    $subquery->addCondition('start_date', $lastDayofLastMonth->format('Y-m-d'), '<');
    $subquery->addCondition('start_date', $firstDayofLastMonth->format('Y-m-d'), '>');
    $subquery->addCondition('end_date', $lastDayofLastMonth->format('Y-m-d'), '<=');
    $bookedRoomNids = $subquery->processQuery()->fetchCol();
    $srno = 1;
    foreach ($bookedRoomNids as $bookedRoomNid) {
      $submission = WebformSubmission::load($bookedRoomNid);
      if ($submission) {
        $data['rows'][] = [
          'srno' => $srno,
          'Employee Name' => $submission->getElementData('employee_name'),
          'Employee Email' => $submission->getElementData('employee_email'),
          'Start Date' => $submission->getElementData('start_date'),
          'End Date' => $submission->getElementData('end_date'),
          'Guesthouse' => $this->getName($submission->getElementData('guesthouse'), 'taxonomy_term'),
          'Occupancy' => $submission->getElementData('room_occupancy'),
          'Room' => $this->getName($submission->getElementData('assign_room'), 'node'),
          'Total Cost' => $submission->getElementData('total_amount'),
          'Created At' => date('Y-m-d h:i:s', $submission->getCreatedTime()),
          'Owner' => $this->getName($submission->getOwnerId(), 'user'),
        ];
        $srno++;
      }
    }
    // Generate the CSV content.
    // Define the CSV file path.
    $csvFileName = 'monthly_report_from_' . $firstDayofLastMonth->format('Y-m-d') . '-To-' . $lastDayofLastMonth->format('Y-m-d') . '.csv';
    $csvFilePath = 'public://report/' . $csvFileName;
    // Define the zip file path.
    $zipFileName = 'monthly_report_from_' . $firstDayofLastMonth->format('Y-m-d') . '-To-' . $lastDayofLastMonth->format('Y-m-d') . '.zip';
    $zipFilePath = 'public://report/' . $zipFileName;

    // Open the CSV file for writing.
    $csvFile = fopen($csvFilePath, 'w');
    // Check if the CSV file was successfully opened.
    if ($csvFile) {
      // Write the header row to the CSV file.
      fputcsv($csvFile, array_keys($data['rows'][0]));
      foreach ($data['rows'] as $row) {
        fputcsv($csvFile, $row);
      }
      // Close the CSV file.
      fclose($csvFile);
    }
    else {
      \Drupal::messenger()->addError('Failed to open the CSV file for writing.');
    }
    // Create zip for csv.
    $zip = new \ZipArchive();
    if ($zip->open(\Drupal::service('file_system')->realpath($zipFilePath), \ZipArchive::CREATE) == TRUE) {
      $zip->addFile(\Drupal::service('file_system')->realpath($csvFilePath), $csvFileName);
      $zip->close();
    }
    else {
      \Drupal::logger('custom_booking')->error('failed to create zip archive ');
    }
    unlink($csvFilePath);
    // Setup email params.
    $module = 'custom_booking';
    $key = 'monthly_report_email_notification_key';
    $to = \Drupal::config('system.site')->get('mail');
    $subject = \Drupal::config('user.mail')->get('report_mail.subject');
    $subject = str_replace('<START>', $firstDayofLastMonth->format('Y-m-d'), $subject);
    $subject = str_replace('<END>', $lastDayofLastMonth->format('Y-m-d'), $subject);
    $params = [
      'subject' => $subject,
      'message' => \Drupal::config('user.mail')->get('report_mail.body'),
    ];
    $file['filepath'] = $zipFilePath;
    $file['filemime'] = mime_content_type($zipFilePath);
    $params['attachments'][] = $file;
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $result = \Drupal::service('plugin.manager.mail')->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
    // Check email status.
    if ($result['result'] === TRUE) {
      \Drupal::messenger()->addStatus('Email sent successfully with attachment.');
    }
    else {
      \Drupal::messenger()->addError('Failed to send the email with attachment.');
    }
  }

}

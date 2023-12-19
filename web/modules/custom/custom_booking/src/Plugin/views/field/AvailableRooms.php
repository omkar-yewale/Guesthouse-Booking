<?php

namespace Drupal\custom_booking\Plugin\views\field;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a custom Views field for available rooms.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("available_rooms")
 */
class AvailableRooms extends FieldPluginBase {


  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The common services.
   *
   * @var \Drupal\custom_booking\Services\CommonServices
   */
  protected $commonServices;

  /**
   * Constructs a new AvailableRooms object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin_id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\custom_booking\Services\CommonServices $commonServices
   *   The common services.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $request, $commonServices) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentDisplay = $configuration['view']->current_display;
    $this->request = $request;
    $this->commonServices = $commonServices;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('common_services')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->currentDisplay = $view->current_display;
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // First check whether the field should be hidden,
    // if the value(hide_alter_empty = TRUE)
    // the rewrite is empty (hide_alter_empty = FALSE).
    $options['hide_alter_empty'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $nid = $node->get('nid')->getValue()[0]['value'];
    $capacity = $node->get('field_room_capacity')->getValue()[0]['value'];
    $guesthouseId = $node->get('field_guesthouse')->getValue()[0]['target_id'];
    $startDate = $this->request->query->get('start_date') ?? date("Y-m-d");
    $endDate = $this->request->query->get('end_date') ?? date("Y-m-d");
    $class = 'occupancy-green';
    $output = '';
    if ($startDate != '' && $endDate != '') {
      // Get booking Ids.
      $bookedRoomNids = $this->commonServices->getBookedNids($startDate, $endDate, $nid, $guesthouseId);
      // Checking for single occupancy booking.
      if (count($bookedRoomNids) == 1) {
        $submission = WebformSubmission::load(reset($bookedRoomNids));
        if ($submission) {
          $occupancy = $submission->getElementData('room_occupancy');
          if ($occupancy == 'single') {
            $class = 'occupancy-orange';
            $output = 'Booked for single occupancy';
          }
          else {
            $available = $capacity - count($bookedRoomNids);
            $output = $available > 0 ? $available . ' Bed' . ($available > 1 ? 's' : '') . ' available' : 'Fully Booked';
            $class = $available > 0 ? 'occupancy-yellow' : 'occupancy-red';
            if ($capacity == $available) {
              $class = 'occupancy-green';
            }
          }
        }
      }
      else {
        $available = $capacity - count($bookedRoomNids);
        $output = $available > 0 ? $available . ' Bed' . ($available > 1 ? 's' : '') . ' available' : 'Fully Booked';
        $class = $available > 0 ? 'occupancy-yellow' : 'occupancy-red';
        if ($capacity == $available) {
          $class = 'occupancy-green';
        }
      }
    }
    else {
      $output = 'Available';
    }

    return [
      '#markup' => '<div class="' . $class . '">' . $output . '</div>',
    ];
  }

}

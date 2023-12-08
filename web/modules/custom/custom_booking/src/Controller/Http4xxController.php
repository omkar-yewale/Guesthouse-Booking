<?php

namespace Drupal\custom_booking\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses of node metatags.
 */
class Http4xxController extends ControllerBase {

  /**
   * Gets meta tags for a node.
   */
  public function on404() {
    // Return a render array using the template.
    return [
      '#theme' => 'error_404',
    ];
  }

}

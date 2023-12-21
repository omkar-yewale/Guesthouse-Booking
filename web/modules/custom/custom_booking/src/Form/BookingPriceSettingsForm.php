<?php

namespace Drupal\custom_booking\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for the booking price.
 */
class BookingPriceSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_booking_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_booking.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_booking.settings');

    $form['booking_cost'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Booking Cost'),
    ];

    $form['booking_cost']['shared_room_cost'] = [
      '#type' => 'number',
      '#title' => $this->t('Shared Room Cost Per Day'),
      '#default_value' => $config->get('shared_room_cost') ?? 300,
      '#required' => TRUE,
      '#min' => 100,
      '#max' => 1000,
    ];

    $form['booking_cost']['single_room_cost'] = [
      '#type' => 'number',
      '#title' => $this->t('Single Room Cost Per Day'),
      '#default_value' => $config->get('single_room_cost') ?? 500,
      '#required' => TRUE,
      '#min' => 100,
      '#max' => 1000,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('custom_booking.settings')
      ->set('shared_room_cost', $form_state->getValue('shared_room_cost'))
      ->set('single_room_cost', $form_state->getValue('single_room_cost'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

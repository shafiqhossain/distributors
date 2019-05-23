<?php

namespace Drupal\distributors\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Setting.
 */
class DistributorsSetting extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'distributors_setting';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'distributors.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Implements admin settings form.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('distributors.settings');
    $form['map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['map']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API key'),
      '#default_value' => $config->get('api_key') ? $config->get('api_key') : '',
      '#required' => TRUE,
      '#description' => $this->t('To create a new key, visit <a href="@api-url">https://developers.google.com/maps/documentation/javascript/get-api-key</a>', ['@api-url' => 'https://developers.google.com/maps/documentation/javascript/get-api-key']),
    ];
    $form['map']['map_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Height'),
      '#default_value' => $config->get('map_height') ? $config->get('map_height') : '500px',
    ];
    $form['map']['map_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Width'),
      '#default_value' => $config->get('map_width') ? $config->get('map_width') : '500px',
    ];
    $form['map']['map_zoom_level'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Zoom Level'),
      '#default_value' => $config->get('map_zoom_level') ? $config->get('map_zoom_level') : 2,
    ];
    $form['map']['proximity'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Proximity'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['map']['proximity']['map_distance'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Within Distance'),
      '#default_value' => $config->get('map_distance') ? $config->get('map_distance') : 250,
    ];
    $form['map']['proximity']['map_distance_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Distance Unit'),
      '#options' => ['miles' => 'Miles', 'km' => 'Km'],
      '#default_value' => $config->get('map_distance_unit') ? $config->get('map_distance_unit') : 'miles',
    ];
    $form['map']['proximity']['map_initial_filter'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Initial Filter by Language'),
      '#default_value' => $config->get('map_initial_filter') ? $config->get('map_initial_filter') : 'nl|NL,en|',
      '#description' => $this->t('Display initial markers over the map based on language and country. Add multiple records by comma separated and as < language id > | < country id>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('distributors.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('map_height', $form_state->getValue('map_height'))
      ->set('map_width', $form_state->getValue('map_width'))
      ->set('map_zoom_level', $form_state->getValue('map_zoom_level'))
      ->set('map_distance', $form_state->getValue('map_distance'))
      ->set('map_distance_unit', $form_state->getValue('map_distance_unit'))
      ->set('map_initial_filter', $form_state->getValue('map_initial_filter'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

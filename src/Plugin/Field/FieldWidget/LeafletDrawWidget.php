<?php

/**
 * @file
 * Contains \Drupal\bbox\Plugin\Field\FieldWidget\LinkWidget.
 */

namespace Drupal\bbox\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'Leaflet Draw' widget.
 *
 * @FieldWidget(
 *   id = "bbox_leaflet_draw",
 *   label = @Translation("Leaflet Draw"),
 *   field_types = {
 *     "bbox"
 *   }
 * )
 */
class LeafletDrawWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];
    $html_id = Html::getId('leaflet-widget');
    $element['leaflet_map']['#markup'] = '<div class="leaflet-widget" id="' . $html_id . '"></div>';

    $element['#attached']['library'][] = 'bbox/leaflet';
    $element['#attached']['library'][] = 'bbox/field';

    $element['#attached']['drupalSettings']['bbox']['widgets'][] = $html_id;

    $element['northeast_lng'] = array(
      '#title' => $this->t('northeast lng'),
      '#default_value' => $item->northeast_lng,
      '#type' => 'textfield',
      '#attributes'=> array(
        'class' => array(
          'northeast-lng'
        )
      )
    );

    $element['northeast_lat'] = array(
      '#title' => $this->t('northeast lat'),
      '#default_value' => $item->northeast_lat,
      '#type' => 'textfield',
      '#attributes'=> array(
        'class' => array(
          'northeast-lat'
        )
      )
    );

    $element['southwest_lng'] = array(
      '#title' => $this->t('southwest lng'),
      '#default_value' => $item->southwest_lng,
      '#type' => 'textfield',
      '#attributes'=> array(
        'class' => array(
          'southwest-lng'
        )
      )
    );

    $element['southwest_lat'] = array(
      '#title' => $this->t('southwest lat'),
      '#default_value' => $item->southwest_lat,
      '#type' => 'textfield',
      '#attributes'=> array(
        'class' => array(
          'southwest-lat'
        )
      )
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    // $summary[] = $this->t('Test');

    return $summary;
  }
}

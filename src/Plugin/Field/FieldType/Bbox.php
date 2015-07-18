<?php

/**
 * @file
 * Contains \Drupal\bbox\Plugin\Field\FieldType\Bbox.
 */

namespace Drupal\bbox\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'bounding box' field type.
 *
 * @FieldType(
 *   id = "bbox",
 *   label = @Translation("Bounding Box"),
 *   description = @Translation("This field stores a bounding box."),
 *   category = @Translation("Other"),
 *   default_widget = "bbox_leaflet_draw",
 *   default_formatter = "text_default"
 * )
 */
class Bbox extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'northeast_lng' => array(
          'type' => 'float',
        ),
        'northeast_lat' => array(
          'type' => 'float',
        ),
        'southwest_lng' => array(
          'type' => 'float',
        ),
        'southwest_lat' => array(
          'type' => 'float',
        ),
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = array();

    return $constraints;
  }
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['northeast_lng'] = DataDefinition::create('float')
      ->setLabel(t('northeast lng'))
      ->setRequired(TRUE);

    $properties['northeast_lat'] = DataDefinition::create('float')
      ->setLabel(t('Northeast lat'))
      ->setRequired(TRUE);

    $properties['southwest_lng'] = DataDefinition::create('float')
      ->setLabel(t('Southwest lng'))
      ->setRequired(TRUE);

    $properties['southwest_lat'] = DataDefinition::create('float')
      ->setLabel(t('Southwest lat'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }
}

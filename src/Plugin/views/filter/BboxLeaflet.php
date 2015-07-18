<?php

/**
 * @file
 * Contains \Drupal\bbox\Plugin\views\filter\BboxLeaflet.
 */

namespace Drupal\bbox\Plugin\views\filter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Basic textfield filter to handle string filtering commands
 * including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("bbox_leaflet")
 */
class BboxLeaflet extends FilterPluginBase {

  // exposed filter options
  protected $alwaysMultiple = TRUE;

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['expose']['contains']['required'] = array('default' => FALSE);

    return $options;
  }

  /**
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  function operators() {
    $operators = array(
      'between' => array(
        'title' => $this->t('Between'),
        'short' => $this->t('<>'),
        'method' => 'opBetween',
        'values' => 2,
      )
    );

    return $operators;
  }

  /**
   * Build strings from the operators() for 'select' options
   */
  public function operatorOptions($which = 'title') {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = '';
    if (!empty($options[$this->operator])) {
      $output = SafeMarkup::checkPlain($options[$this->operator]);
    }
    if (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ' . SafeMarkup::checkPlain($this->value);
    }
    return $output;
  }

  protected function operatorValues($values = 1) {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * Provide a simple textfield for equality
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ':input[name="options[operator]"]';
    }
    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // exposed and locked.
        $which = 'value';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    if ($which == 'all' || $which == 'value') {
      $html_id = Html::getId('leaflet-widget');
      $form['wrapper'] = array(
        '#type' => 'fieldset',
        '#title' => $this->exposedInfo()['label'],
        '#attributes' => array(
          'class' => array(
            'fieldgroup'
          )
        )
      );

      $form['wrapper']['leaflet_map']['#markup'] = '<div class="leaflet-widget" id="' . $html_id . '"></div>';

      $form['#attached']['library'][] = 'bbox/leaflet';
      $form['#attached']['library'][] = 'bbox/views';

      $form['#attached']['drupalSettings']['bbox']['widgets'][] = $html_id;

      $form['wrapper'][$identifier] = array(
        '#type' => 'textfield',
        '#size' => 30,
        '#attributes' => array(
          'class' => array(
            'bbox-value'
          )
        ),
        '#default_value' => $this->value,
      );

      $user_input = $form_state->getUserInput();
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }

      if ($which == 'all') {
        // Setup #states for all operators with one value.
        foreach ($this->operatorValues(1) as $operator) {
          $form['value']['#states']['visible'][] = array(
            $source => array('value' => $operator),
          );
        }
      }
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = array(
        '#type' => 'value',
        '#value' => NULL
      );
    }
  }

  function operator() {
    return $this->operator == '=' ? 'LIKE' : 'NOT LIKE';
  }

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  protected function opBetween($field) {
    $values = explode('|', $this->value);
    $field_base_name = substr($field, 0, -8);

    $input = array(
      'southwest' => array(
        'lng' => (float) $values[3],
        'lat' => (float) $values[2]
      ),
      'northeast' => array(
        'lng' => (float) $values[1],
        'lat' => (float) $values[0]
      ),
    );

    $south_west = array(
      'lng' => $field_base_name . '_southwest_lng',
      'lat' => $field_base_name . '_southwest_lat'
    );

    $north_east = array(
      'lng' => $field_base_name . '_northeast_lng',
      'lat' => $field_base_name . '_northeast_lat'
    );

    $types = array('lat', 'lng');

    foreach ($types as $type) {
      $case = 'CASE WHEN ' .
       // Articles fully inside the boundingbox
      $south_west[$type] . ' BETWEEN ' . $input['southwest'][$type] . ' AND ' . $input['northeast'][$type] .
      ' AND ' .
      $north_east[$type] . ' BETWEEN ' . $input['southwest'][$type] . ' AND ' . $input['northeast'][$type] .
      ' THEN 1 ' .

      // Articles partly inside our bounding box.
      'WHEN ' .
      $south_west[$type] . ' BETWEEN ' . $input['southwest'][$type] . ' AND ' . $input['northeast'][$type] .
      ' OR ' .
      $north_east[$type] . ' BETWEEN ' . $input['southwest'][$type] . ' AND ' . $input['northeast'][$type] .
      ' THEN 2 ' .

      // Articles fully inside AND bigger than our bounding box.
      'WHEN ' .
      $north_east[$type] . ' > ' . $input['northeast'][$type] .
      ' AND ' .
      $south_west[$type] . ' < ' . $input['southwest'][$type] .
      ' THEN 3 END ';

      $this->query->addField(NULL,
        $case,
        'bbox_type_' . $type
      );

      $this->query->addField(NULL, $north_east[$type] . ' - ' . $south_west[$type], 'bbox_sort_' . $type);
      $this->query->addWhereExpression($this->options['group'], $case);
    }

    $this->query->addOrderBy(NULL, NULL, ASC, 'bbox_type_lat');
    $this->query->addOrderBy(NULL, NULL, ASC, 'bbox_type_lng');

    $this->query->addOrderBy(NULL, NULL, ASC, 'bbox_sort_lat');
    $this->query->addOrderBy(NULL, NULL, ASC, 'bbox_sort_lng');
  }
}

<?php

namespace Drupal\collage\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;
use Drupal\Component\Utility\Html;

/**
 * @DsField(
 *   id = "collage",
 *   title = @Translation("Collage"),
 *   entity_type = "media",
 *   provider = "collage"
 * )
 */
class Collage extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $wrapper_id = Html::getUniqueId('collage-wrapper');
    $configuration = $this->getConfiguration();
    $bricks = $configuration['build']['childs'];

    foreach ($bricks as &$brick) {
      $brick['#attributes']['data-collage-id'] = $brick['#media']->id();
    }

    $parent = $bricks[0]['#media']->__get('collage_parent_entity');
    $parent_field = $parent->field_bricks;
    $settings_map = [];

    $type_configuration = $bundle_label = \Drupal::config('media_entity.bundle.collage')->get('type_configuration');
    $breakpoints_text = $type_configuration['collage_breakpoints'];
    $breakpoints = [];

    foreach (explode("\n", $breakpoints_text) as $row) {
      $row_exploded = explode('|', $row);
      $breakpoints[Html::getClass($row_exploded[0])] = [
        'label' => $row_exploded[0],
        'id' => Html::getClass($row_exploded[0]),
        'min_width' => (int) $row_exploded[1],
        'columns' => (int) $row_exploded[2],
      ];
    }

    foreach ($parent_field as $field_value) {
      if (isset($field_value->options['collage'])) {
        $settings_map[$field_value->target_id] = json_decode($field_value->options['collage']);
      }
    }

    $css = '';

    foreach ($breakpoints as $breakpoint) {
      $css .= '@media screen and (min-width: ' . $breakpoint['min_width'] . "px) {\n";

      foreach ($settings_map as $media_id => $item) {
        $css .= '  #' . $wrapper_id . ' [data-collage-id="' . $media_id . '"] {' . "\n";
        $css .= "  }\n";
      }

      $css .= "}\n";
    }


    $render = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['collage-wrapper'],
        'id' => $wrapper_id
      ],
      'children' => $bricks
    ];

    $render['#attached']['html_head'][] = [
      [
        '#tag' => 'style',
        '#value' => $css
      ],
      'collage'
    ];

    return $render;
  }

  public function isAllowed() {
    return $this->bundle() == 'collage';
  }

}

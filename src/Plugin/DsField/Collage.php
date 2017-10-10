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
    $cache_tags = $this->entity()->getCacheTags();

    if (!count($bricks)) { return; }

    $settings_map = [];
    $parent = $bricks[0]['#media']->__get('collage_parent_entity');
    $parent_field = $parent->field_bricks;

    foreach ($parent_field as $field_value) {
      if (isset($field_value->options['collage'])) {
        $collage_data = json_decode($field_value->options['collage']);
        if ($collage_data) {
          $settings_map[$field_value->target_id] = $collage_data;
        }
      }
    }

    foreach ($bricks as &$brick) {
      $brick['#attributes']['data-collage-id'] = $brick['#media']->id();
      $cache_tags = array_merge($brick['#media']->getCacheTags(), $cache_tags);

      if (isset($settings_map[$brick['#media']->id()])) {
        foreach ($settings_map[$brick['#media']->id()] as $breakpoint => $breakpoint_brick_settings) {
          $brick['#attributes']['data-collage-' . $breakpoint . '-width'] = $breakpoint_brick_settings->width;
          $brick['#attributes']['data-collage-' . $breakpoint . '-height'] = $breakpoint_brick_settings->height;
        }
      }
    }

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

    $css = '.collage-wrapper { position: relative; width: 100%; float: left; } [data-collage-id] { position: absolute; } ' . "\n";

    $breakpoint_index = 0;

    foreach ($breakpoints as $breakpoint) {
      $highest = 0;

      foreach ($settings_map as $media_id => $item) {
        if (isset($item->{$breakpoint['id']})) {
          $possible_higher = $item->{$breakpoint['id']}->top + $item->{$breakpoint['id']}->height;
          $highest = max($highest, $possible_higher);
        }
      }

      if ($breakpoint_index > 0) {
        $css .= '@media screen and (min-width: ' . $breakpoint['min_width'] . "px) {\n";
      }

      $ratio = 100 / $breakpoint['columns'] * $highest;
      $css .= '  #' . $wrapper_id . ':after { content: ""; float: left; width: 100%; padding-bottom: ' . $ratio . '%; }' . "\n";

      foreach ($settings_map as $media_id => $item) {
        $css .= '  #' . $wrapper_id . ' [data-collage-id="' . $media_id . '"] {' . "\n";
        $css .= "  top: " .  (100 / $highest * $item->{$breakpoint['id']}->top) . "%;\n";
        $css .= "  left: " .  (100 / $breakpoint['columns'] * $item->{$breakpoint['id']}->left) . "%;\n";
        $css .= "  width: " .  (100 / $breakpoint['columns'] * $item->{$breakpoint['id']}->width) . "%;\n";
        $css .= "  height: " .  (100 / $highest * $item->{$breakpoint['id']}->height) . "%;\n";
        $css .= "  z-index: " .  $item->{$breakpoint['id']}->zIndex . ";\n";
        $css .= "  }\n";
      }

      if ($breakpoint_index > 0) {
        $css .= "}\n";
      }

      $breakpoint_index++;
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

    $render['#cache'] = [
      'tags' => $cache_tags
    ];

    return $render;
  }

  public function isAllowed() {
    return $this->bundle() == 'collage';
  }

}

<?php

/**
 * @file
 * CustomModalController class.
 */

namespace Drupal\collage\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Render\FormattableMarkup;

class CollageModal extends ControllerBase {

  public function modal($entity_type, $entity_id, $field_name, $collage_id) {
    $bricks = $this->getCollageBricks($entity_type, $entity_id, $field_name, $collage_id);
    $current_theme = \Drupal::config('system.theme')->get('default');
    $break_points = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($current_theme);

    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '95%',
      'height' => '95%',
      'resizable' => FALSE
    ];

    $modal_contents = [
      '#prefix' => '<div class="collage-widget-wrapper"><div class="ui-tabs">',
      '#suffix' => '</div></div>',
      'tabs' => [
        '#prefix' => '<ul class="">',
        '#suffix' => '</ul>'
      ]
    ];

    foreach ($break_points as $break_point_id => $break_point) {

      $media_query = $break_point->getMediaQuery();
      $media_query_exploded = explode('min-width:', $media_query);
      $min_width = trim(explode(')', $media_query_exploded[1])[0]);

      if (!$min_width) {
        $min_width = '320px';
      }

      $modal_contents['tabs'][$break_point_id] = [
        '#markup' => $break_point->getLabel(),
        '#prefix' => '<li><a href="#' . $break_point_id . '">',
        '#suffix' => '</a></li>'
      ];

      $modal_contents[$break_point_id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['collage-widget-tab'],
          'id' => $break_point_id,
        ],
        'inner' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['collage-widget-tab-inner'],
            'style' => 'width: ' . $min_width . ';'
          ],
        ]
      ];

      foreach ($bricks as $brick) {
        $modal_contents[$break_point_id]['inner']['collage-item-' . $break_point_id . '-' . $brick->{$field_name . '_target_id'}] = [
          '#prefix' => '<div class="collage-item ui-widget-content" id="collage-item-' . $break_point_id . '-' . $brick->{$field_name . '_target_id'} . '">',
          '#markup' => '<span class="ui-widget-header">collage-item: ' . $brick->{$field_name . '_target_id'} . '</span>',
          '#suffix' => '</div>'
        ];
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t('Edit collage'), $modal_contents, $options));

    return $response;
  }

  private function getCollageBricks($entity_type, $entity_id, $field_name, $collage_id) {
    $query = db_select($entity_type . '__' . $field_name, 'f')
      ->condition('deleted', FALSE)
      ->condition('entity_id', $entity_id)
      ->orderBy('delta')
      ->fields('f');

    $result = $query->execute();

    $started = FALSE;
    $start_depth = NULL;
    $stopped = FALSE;

    $children = [];

    foreach ($result as $row) {
      if ($row->{$field_name . '_depth'} < $start_depth) {
        $stopped = TRUE;
      }

      if (!$stopped && $started) {
        $children[] = $row;
      }

      if ($row->{$field_name . '_target_id'} == $collage_id) {
        $started = TRUE;
        $start_depth = $row->{$field_name . '_depth'};
      }
    }

    return $children;
  }
}
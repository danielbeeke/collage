<?php

/**
 * @file
 * CustomModalController class.
 */

namespace Drupal\collage\Controller;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;

class CollageModal extends ControllerBase {

  public function modal($entity_type, $entity_id, $field_name, $collage_id) {
    $collage = entity_load('media', $collage_id);
    $bricks = $this->getCollageBricks($entity_type, $entity_id, $field_name, $collage_id);
    $type_configuration = $bundle_label = \Drupal::config('media_entity.bundle.' . $collage->bundle())->get('type_configuration');
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

    foreach ($breakpoints as $breakpoint) {
      $modal_contents['tabs'][$breakpoint['id']] = [
        '#markup' => $breakpoint['label'],
        '#prefix' => '<li><a href="#' . $breakpoint['id'] . '">',
        '#suffix' => '</a></li>'
      ];

      $modal_contents[$breakpoint['id']] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['collage-widget-tab'],
          'id' => $breakpoint['id'],
        ],
        'inner' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['collage-widget-tab-inner'],
            'data-breakpoint' => $breakpoint['id']
          ],
        ]
      ];

      foreach ($bricks as $brick) {
        $modal_contents[$breakpoint['id']]['inner']['collage-item-' . $breakpoint['id'] . '-' . $brick->entity->id()] = [
          '#markup' => '<span class="ui-widget-header">' . $brick->entity->name->value . '</span>',
          '#type' => 'container',
          '#attributes' => [
            'class' => ['collage-item', 'ui-widget-content'],
            'data-collage-item-id' => $brick->entity->id(),
            'data-breakpoint' => $breakpoint['id'],
            'id' => 'collage-item-' . $breakpoint['id'] . '-' . $brick->entity->id()
          ]
        ];
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new SettingsCommand([
      'collage_breakpoints' => $breakpoints,
      'collage_context' => [
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'field_name' => $field_name,
        'collage_id' => $collage_id
      ]
    ], TRUE));
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
        $row->entity = entity_load('media', $row->{$field_name . '_target_id'});
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
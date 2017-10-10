<?php

/**
 * @file
 * CustomModalController class.
 */

namespace Drupal\collage\Controller;


use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

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
      'dialogClass' => 'collage-widget-popup',
      'width' => '95%',
      'height' => '95%',
      'resizable' => FALSE
    ];

    $modal_contents = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['collage-widget-wrapper']
      ],
      'inner' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['ui-tabs content-header']
        ],
        'tabs_wrapper' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['layout-container']
          ],
          'tabs' => [
            '#theme' => 'menu_local_tasks',
            '#primary' => []
          ]
        ],
      ],
      'content' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['layout-container']
        ],
      ]
    ];

    foreach ($breakpoints as $breakpoint) {
      $url = Url::fromUserInput('#' . $breakpoint['id']);

      $modal_contents['inner']['tabs_wrapper']['tabs']['#primary'][$breakpoint['id']] = [
        '#theme' => 'menu_local_task',
        '#link' => [
          'title' => $breakpoint['label'],
          'url' => $url
        ],
        '#weight' => 1,
        '#access' => new AccessResultAllowed(),
      ];

      $modal_contents['content'][$breakpoint['id']] = [
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
        $brick_settings = unserialize($brick->{$field_name . '_options'});
        $saved_collage = isset($brick_settings['collage']) ? json_decode($brick_settings['collage'], TRUE) : [];

        $view_mode = isset($brick_settings['view_mode']) && $brick_settings['view_mode'] ? $brick_settings['view_mode'] : 'default';

        $modal_contents['content'][$breakpoint['id']]['inner']['collage-item-' . $breakpoint['id'] . '-' . $brick->entity->id()] = [
          'iframe' => [
            '#type' => 'html_tag',
            '#tag' => 'iframe',
            '#attributes' => [
              'style' => 'width: 100%; height: 100%; border: 0;',
              'src' => '/collage/viewmode/media/' . $brick->entity->id() . '/' . $view_mode . '/' . $breakpoint['id'],
            ]
          ],
          'z_index' => [
            '#type' => 'number',
            '#attributes' => [
              'min' => 1,
              'max' => 999
            ],
            '#value' => isset($saved_collage[$breakpoint['id']]['zIndex']) ? (int) $saved_collage[$breakpoint['id']]['zIndex'] : 1
          ],
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
      if ((int) $row->{$field_name . '_depth'} < $start_depth || ($started && (int) $row->{$field_name . '_depth'} > $start_depth + 1)) {
        $stopped = TRUE;
      }

      if (!$stopped && $started && (int) $row->{$field_name . '_depth'} == $start_depth) {
        $row->entity = entity_load('media', $row->{$field_name . '_target_id'});
        $children[] = $row;
      }

      if ($row->{$field_name . '_target_id'} == $collage_id) {
        $started = TRUE;
        $start_depth = (int) $row->{$field_name . '_depth'} + 1;
      }
    }

    return $children;
  }

  public function viewMode ($entity_type, $entity_id, $view_mode, $breakpoint) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $entity_view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $entity = $entity_storage->load($entity_id);
    $render = $entity_view_builder->view($entity, $view_mode);

    $render['#attached']['library'][] = 'collage/iframe';

    return \Drupal::service('bare_html_page_renderer')
    ->renderBarePage(
      $render,
      'Collage entity',
      'page',
      []
    );
  }

  public function svg ($one_column_width) {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="' . $one_column_width  . '" height="' . $one_column_width . '" viewBox="0 0 ' . $one_column_width  . ' ' . $one_column_width . '">';
    $svg .= '<rect width="1" height="1" x="0" y="0" fill="#444"/>';
    $svg .= '<rect width="1" height="1" x="' . ($one_column_width - 1) . '" y="' . ($one_column_width - 1) . '" fill="#444"/>';
    $svg .= '<rect width="1" height="1" x="' . 0 . '" y="' . ($one_column_width - 1) . '" fill="#444"/>';
    $svg .= '<rect width="1" height="1" x="' . ($one_column_width - 1) . '" y="' . 0 . '" fill="#444"/>';

    $svg .= '</svg>';

    $headers = [
      'Content-Type' => 'image/svg+xml',

    ];

    return new Response($svg, 200, $headers);
  }
}

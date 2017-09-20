<?php

namespace Drupal\collage\Plugin\MediaEntity\Type;

use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides media type plugin for Document.
 *
 * @MediaType(
 *   id = "collage",
 *   label = @Translation("Collage"),
 *   description = @Translation("Provides collages.")
 * )
 */
class Collage extends MediaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $current_theme = \Drupal::config('system.theme')->get('default');
    $break_points = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup($current_theme);



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    return $this->config->get('icon_base') . '/collage.png';
  }

}

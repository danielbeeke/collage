<?php

namespace Drupal\collage\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;

/**
 * @FieldWidget(
 *   id = "collage",
 *   label = @Translation("Collage"),
 *   description = @Translation("A collage creator."),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class Collage extends WidgetBase {

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $callback_object = $build_info['callback_object'];
    $entity = $callback_object->getEntity();
    $inline_entity_forms = $form_state->get('inline_entity_form');
    $current_inline_entity_form = $inline_entity_forms[$form['#ief_id']];
    $field_name = $current_inline_entity_form['instance']->getName();

    $link_url = Url::fromRoute('collage.modal', [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'field_name' => $field_name,
      'collage_id' => $items->getEntity()->id(),
    ]);

    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--small'],
        'data-dialog-type' => 'modal',
      ]
    ]);

    $element = [
      '#type' => 'container',
      '#attached' => [
        'library' => ['collage/widget']
      ],
      'link' => [
        '#type' => 'markup',
        '#markup' => Link::fromTextAndUrl(t('Edit collage'), $link_url)->toString(),
        '#attached' => ['library' => ['core/drupal.dialog.ajax']]
      ]
    ];

    return $element;
  }

}

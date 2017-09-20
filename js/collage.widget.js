(function ($, Drupal) {

  Drupal.behaviors.collage = {
    attach: function (context, settings) {

      // $('.collage-settings', context).hide();

      if ($(context).hasClass('collage-widget-wrapper')) {
        $('.ui-tabs', context).tabs();

        $('.collage-item')
        .draggable({
          containment: 'parent',
          grid: [ 50, 50 ]
        })
        .resizable({
          handles: 'all',
          grid: 50
        });
      }

    }
  };

} (jQuery, Drupal));

(function ($, Drupal) {

  Drupal.behaviors.collage = {
    attach: function (context, settings) {

      $('.collage-settings', context).hide();

      if ($('.collage-widget-wrapper', context).length) {
        var initTabs = function () {
          $('.tabs a', context).on('click', function (e) {
            e.preventDefault();
            $('.tabs .is-active', context).removeClass('is-active');
            $(this).parent().addClass('is-active');
            $(this).closest('li').addClass("active").siblings().removeClass("active");
            $($(this).attr('href')).show().siblings('.collage-widget-tab').hide();
          });

          $('.tabs a:first', context).click();
        };

        var updateTextFields = function () {
          var data = {};

          $.each(settings.collage_breakpoints, function (delta, breakpoint)  {
            $('.collage-item', breakpoint.tab).each(function (itemDelta, item) {

              var itemData = {
                top: parseInt($(item).css('top')) / breakpoint.oneColumn,
                left: parseInt($(item).css('left')) / breakpoint.oneColumn,
                width: parseInt($(item).css('width')) / breakpoint.oneColumn,
                height: parseInt($(item).css('height')) / breakpoint.oneColumn,
                zIndex: $(item).css('z-index')
              };

              if (!data[item.dataset.collageItemId]) { data[item.dataset.collageItemId] = {} }
              data[item.dataset.collageItemId][breakpoint.id] = itemData;
            });
          });

          $.each(data, function (mediaId, data) {
            var textField = $('[data-entity-type="' + settings.collage_context.entity_type +
              '"][data-entity-id="' + settings.collage_context.entity_id +
              '"][data-field-name="' + settings.collage_context.field_name +
              '"][data-collage-id="' + settings.collage_context.collage_id +
              '"][data-collage-item-id="' + mediaId + '"]');

            textField.val(JSON.stringify(data));
          });
        };

        var initSavedData = function () {
          $('.collage-settings').each(function (delta, mediaCollageField) {
            var mediaId = mediaCollageField.dataset.collageItemId;
            var savedItemSettings = $(mediaCollageField).val() ? JSON.parse($(mediaCollageField).val()) : {};
            $.each(savedItemSettings, function (breakpoint, itemData) {
              var breakpointData = settings.collage_breakpoints[breakpoint];

              var widgetItem = $('.collage-item[data-collage-item-id="' + mediaId + '"][data-breakpoint="' + breakpoint + '"]');
              widgetItem.css({
                top: itemData.top * breakpointData.oneColumn + 'px',
                left: itemData.left * breakpointData.oneColumn + 'px',
                width: itemData.width * breakpointData.oneColumn + 'px',
                height: itemData.height * breakpointData.oneColumn + 'px',
                zIndex: itemData.zIndex
              });
            })
          });
        };

        var reOrderItems = function (tab, clickedItem) {
          var zIndexStack = [];
          $('.collage-item', tab).each(function (itemDelta, item) {
            if (item !== clickedItem) {
              zIndexStack.push({
                item: item,
                zIndex: $(item).css('z-index') === 'auto' ? 0 : $(item).css('z-index')
              })
            }
          });

          zIndexStack.sort(function (a, b) {
            return a.zIndex.localeCompare(b.zIndex);
          });

          $(zIndexStack).each(function (zIndexDelta, zIndexItem) {
            $(zIndexItem.item).css('z-index', zIndexDelta)
          });

          $(clickedItem).css('z-index', zIndexStack.length + 1);
        };

        var initItems = function () {
          $.each(settings.collage_breakpoints, function (delta, breakpoint) {
            breakpoint.oneColumn = Math.round(breakpoint.min_width / breakpoint.columns);
            breakpoint.roundedWidth = breakpoint.oneColumn * breakpoint.columns;
            breakpoint.tab = $('.collage-widget-tab-inner[data-breakpoint="' + breakpoint.id + '"]');

            breakpoint.tab
            .width(breakpoint.roundedWidth + 'px')
            .height(breakpoint.roundedWidth + 'px');
            $('.collage-item', breakpoint.tab)
            .css({
              width: breakpoint.oneColumn + 'px',
              height: breakpoint.oneColumn + 'px'
            })
            .draggable({
              containment: 'parent',
              grid: [breakpoint.oneColumn, breakpoint.oneColumn],
              scroll: true,
              scrollSensitivity: 100,
              stop: function () {
                updateTextFields();
              }
            })
            .resizable({
              handles: 'all',
              grid: breakpoint.oneColumn,
              stop: function () {
                updateTextFields();
              }
            })
            .on('mousedown', function () {
              reOrderItems(breakpoint.tab, this);
            })
          });
        };

        initItems();
        initTabs();
        initSavedData();
      }

    }
  };

} (jQuery, Drupal));

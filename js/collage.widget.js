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
            fixHeight();
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
                zIndex: $('input', item).val()
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

              updateIframeSrc(widgetItem, breakpointData);
            })
          });
        };

        var fixHeight = function () {
          $.each(settings.collage_breakpoints, function (delta, breakpoint) {
            var heighest = 0;

            $('.collage-item', breakpoint.tab).each(function (tabItemDelta, tabItem) {
              var itemHeight = $(tabItem).height();
              var itemTop = $(tabItem).position().top;
              heighest = Math.max(itemTop + itemHeight, heighest);
            });

            var newHeight = heighest + (breakpoint.tab.outerWidth() / breakpoint.columns);
            breakpoint.tab.height(newHeight);
          });
        };

        var roundToColumn = function (valueToRound, oneColumn) {
          valueToRound = parseInt(valueToRound);
          return (Math.round(valueToRound / oneColumn) * oneColumn) + 'px';
        };

        var updateIframeSrc = function (item, breakpoint) {
          var url = $(item).find('iframe').attr('src');
          url = url.split('#')[0];
          url += '#' + JSON.stringify({
            width: roundToColumn($(item).css('width'), breakpoint.oneColumn),
            height: roundToColumn($(item).css('height'), breakpoint.oneColumn),
          });

          $(item).find('iframe').attr('src', url);
        };

        var initItems = function () {
          $.each(settings.collage_breakpoints, function (delta, breakpoint) {
            breakpoint.oneColumn = Math.round(breakpoint.min_width / breakpoint.columns);
            breakpoint.roundedWidth = breakpoint.oneColumn * breakpoint.columns;
            breakpoint.tab = $('.collage-widget-tab-inner[data-breakpoint="' + breakpoint.id + '"]');
            breakpoint.tab.width(breakpoint.roundedWidth + 'px');
            breakpoint.tab.css({
              backgroundImage: 'url("/collage/svg/' + breakpoint.oneColumn + '")'
            });

            $('.collage-item iframe', breakpoint.tab).css('width', breakpoint.min_width);
            $('.collage-item', breakpoint.tab)
            .css({
              width: (breakpoint.oneColumn * 3) + 'px',
              height: (breakpoint.oneColumn * 3) + 'px'
            })
            .draggable({
              // containment: 'parent',
              grid: [breakpoint.oneColumn, breakpoint.oneColumn],
              scroll: true,
              scrollSensitivity: 100,
              stop: function () {
                // We do this to avoid half grid tiles after dragging.
                $(this).css({
                  top: roundToColumn($(this).css('top'), breakpoint.oneColumn),
                  left: roundToColumn($(this).css('left'), breakpoint.oneColumn),
                  width: roundToColumn($(this).css('width'), breakpoint.oneColumn),
                  height: roundToColumn($(this).css('height'), breakpoint.oneColumn)
                });

                updateIframeSrc(this, breakpoint);
                updateTextFields();
              },
              drag: function (event, ui) {
                updateIframeSrc(this, breakpoint);
                fixHeight();
              }
            })
            .resizable({
              handles: 'all',
              grid: breakpoint.oneColumn,
              stop: function () {
                updateIframeSrc(this, breakpoint);
                updateTextFields();
              },
              resize: function (event, ui) {
                updateIframeSrc(this, breakpoint);
                fixHeight();
              }
            });

            $('.collage-item input', breakpoint.tab).on('change', function () {
              var newzIndex = $(this).val();
              var item = $(this).parents('.collage-item')[0];
              $(item).css('z-index', newzIndex);
              updateTextFields();
            })

          });
        };

        initItems();
        initTabs();
        initSavedData();
        setTimeout(function () {
          fixHeight();
        })
      }

    }
  };

} (jQuery, Drupal));

(function ($) {

  var parseUrl = function () {
    var json = decodeURIComponent(location.hash.substr(1));

    if (json) {
      var sizes = JSON.parse(json);

      if (sizes) {
        $('body > *').css(sizes);
      }
    }
  };

  window.addEventListener('hashchange', function () {
    parseUrl();
  });
  parseUrl();

} (jQuery));

/**
 * @file
 * Attaches the leaflet draw widget.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.bboxViewsExposedFilter = {
    attach: function (context, drupalSettings) {

      if (!drupalSettings.bbox.instances) {
        drupalSettings.bbox.instances = {}
      }

      $.each(drupalSettings.bbox.widgets, function (delta, id) {
        if ($('#' + id).length && !$('#' + id).hasClass('leaflet-container')) {
          $('#' + id).css('height', '200px')
          $('#' + id).parent().find('.form-type-textfield').hide()
          var viewsField = $('#' + id).parent().find('.bbox-value')

          var map = L.map(id, {
            attributionControl: false
          }).setView([51.505, -0.09], 13)
          L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png').addTo(map)

          map.on('zoomend moveend', function(e) {
            var bounds = map.getBounds()

            var newVal = bounds.toBBoxString().replace(/,/g, '|')
            var timeout

            if (newVal != viewsField.val()) {
              viewsField.val(newVal)

              clearTimeout(timeout)

              timeout = setTimeout(function () {
                viewsField.change()
              }, 900)
            }
          });

          if (viewsField.val()) {
            var values = viewsField.val().split('|')

            if (values) {
              var bounds = [[values[1], values[0]], [values[3], values[2]]]
              map.fitBounds(bounds)
            }
          }

          drupalSettings.bbox.instances[id] = map
        }
      })

    }
  };

})(jQuery, Drupal, drupalSettings);

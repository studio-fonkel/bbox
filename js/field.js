/**
 * @file
 * Attaches the leaflet draw widget.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.behaviors.bboxWidget = {
    attach: function (context, drupalSettings) {

      if (!drupalSettings.bbox.instances) {
        drupalSettings.bbox.instances = {}
      }

      $.each(drupalSettings.bbox.widgets, function (delta, id) {
        if ($('#' + id).length && !$('#' + id).hasClass('leaflet-container')) {
          $('#' + id).parent().find('.form-type-textfield').hide()
          var northEastLng = $('#' + id).parent().find('.northeast-lng')
          var northEastLat = $('#' + id).parent().find('.northeast-lat')
          var southWestLng = $('#' + id).parent().find('.southwest-lng')
          var southWestLat = $('#' + id).parent().find('.southwest-lat')

          var map = L.map(id, {
            attributionControl: false
          })

          L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png').addTo(map)

          var drawnItems = new L.FeatureGroup()
          map.addLayer(drawnItems)

          if (northEastLng.val() != 0 && northEastLat.val() && southWestLng.val() && southWestLat.val()) {
            var bounds = [[northEastLng.val(), northEastLat.val()], [southWestLng.val(), southWestLat.val()]]
            L.rectangle(bounds, {color: "blue"}).addTo(drawnItems)

            setTimeout(function () {
              map.fitBounds(bounds)
            }, 100)
          }
          else {
            map.setView([52.505, 5], 6)
          }

          var drawControl = new L.Control.Draw({
            draw: {
              polyline: false,
              polygon: false,
              circle: false,
              marker: false,
              rectangle: {
                shapeOptions: {
                  color: 'blue'
                }
              },
            },
            edit: {
              featureGroup: drawnItems,
              remove: false,
              selectedPathOptions: {
                  maintainColor: true,
                  opacity: 0.3
              }
            }
          })

          map.addControl(drawControl)

          map.on('draw:created', function (e) {
            var type = e.layerType
            var layer = e.layer
            drawnItems.clearLayers()
            drawnItems.addLayer(layer)
            writeOut()
          })

          map.on('draw:edited', function (e) {
            writeOut()
          })

          var writeOut = function () {
            // TODO wtf
            drawnItems.eachLayer(function (layer) {
              northEastLng.val(layer._latlngs[2].lat)
              northEastLat.val(layer._latlngs[2].lng)
              southWestLng.val(layer._latlngs[0].lat)
              southWestLat.val(layer._latlngs[0].lng)
            })
          }

          drupalSettings.bbox.instances[id] = map
        }
      })

    }
  };

})(jQuery, Drupal, drupalSettings);

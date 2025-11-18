(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_widget = {
    attach: function (context, settings) {

      $(once('initiate-autocomplete', 'input.address-suggestion-widget', context)).each(function () {
        const form_page = $(this).closest('form');
        let ui_autocomplete = $(this).data('ui-autocomplete');
        const fieldParent = $(this).parent();
        let leafletMap = null;
        ui_autocomplete.options.select = function (event, ui) {
          if("location_field" in settings.address_suggestion && 'location' in  ui.item){
            let location_field = settings.address_suggestion.location_field;
            let type_field = settings.address_suggestion.type_field;
            let longitude = ui.item.location.longitude;
            let latitude = ui.item.location.latitude;
            if(type_field == 'geolocation'){
              form_page.find("input[name*='" + location_field + "[0][lat]']").val(latitude);
              form_page.find("input[name*='" + location_field + "[0][lng]']").val(longitude);
            }
            if(type_field == 'geofield'){
              form_page.find("input[name*='" + location_field + "[0][value][lat]']").val(latitude);
              form_page.find("input[name*='" + location_field + "[0][value][lon]']").val(longitude);
            }
            fieldParent.find('.lon').text(longitude);
            fieldParent.find('.lat').text(latitude);
            let map = fieldParent.parent().find('.map');
            if(map.length) {
              let data = map.data();
              let id = map.attr('id');
              if (!leafletMap) {
                map.remove();
                map = $(`<div id="${id}" class="map"></div>`);
                map.height(data.height);
                map.width(data.width || '100%');
                map.css('z-index', 100);
                fieldParent.parent().append(map);
                leafletMap = L.map(id).setView([latitude, longitude], data.zoom);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  maxZoom: 19,
                  attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(leafletMap);
                L.marker([latitude, longitude]).addTo(leafletMap);
              } else {
                leafletMap.setView([latitude, longitude], data.zoom);
                if (leafletMap.marker) {
                  leafletMap.removeLayer(leafletMap.marker);
                }
                leafletMap.marker = L.marker([latitude, longitude]).addTo(leafletMap);
              }
            }
          }
        }
      });
    }
  }
}(jQuery, Drupal, once));

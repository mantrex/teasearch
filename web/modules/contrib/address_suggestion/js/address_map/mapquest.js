(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_mapquest = {
    attach: function (context, settings) {
      $(once('map', '.map.mapquest', context)).each(function () {
        let data = $(this).data();
        let id = $(this).attr('id');
        if (data.height) {
          $(this).height(data.height);
        }
        if (data.width && data.width !== '') {
          $(this).width(data.width);
        } else {
          $(this).width('100%');
        }

        L.mapquest.key = settings.address_map?.[id].api_key;
        let center = [data.lat, data.lon]
        let map = L.mapquest.map(id, {
          center: center,
          layers: L.mapquest.tileLayer('map'),
          zoom: data.zoom
        });
        map.addControl(L.mapquest.control());

        if (data.address) {
          // create the popup
          const popup = L.popup({closeButton: false})
            .setLatLng(center)
            .setContent(data.address.replace(/\n/g, '<br/>'))
            .openOn(map);
        }
      });

    },
  }
}(jQuery, Drupal, once));

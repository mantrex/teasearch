
(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_mapbox = {
    attach: function (context, settings) {
      $(once('map', '.map.mapbox', context)).each(function () {
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

        mapboxgl.accessToken = settings.address_map?.[id].api_key;

        let map = new mapboxgl.Map({
          container: id,
          style: 'mapbox://styles/mapbox/streets-v9',
          center: [data.lon, data.lat],
          zoom: data.zoom
        });
        map.addControl(new mapboxgl.NavigationControl());
        map.on('style.load', () => {
          map.setFog({}); // Set the default atmosphere style
        });// Create a new marker.

        if (settings.address_map?.[id].points?.length > 0) {
          settings.address_map[id].points.forEach(function (point) {
            const marker = new mapboxgl.Marker()
              .setLngLat([point.lon, point.lat])
              .addTo(map);
            if (point.address) {
              const popup = new mapboxgl.Popup({offset: 25}).setHTML(
                point.address.replace(/\n/g, '<br/>')
              );
              marker.setPopup(popup);
            }
          });
        } else {
          const marker = new mapboxgl.Marker()
            .setLngLat([data.lon, data.lat])
            .addTo(map);
          if (data.address) {
            // create the popup
            const popup = new mapboxgl.Popup({offset: 25}).setHTML(
              data.address.replace(/\n/g, '<br/>')
            );
            marker.setPopup(popup);
          }
        }
      });

    },
  }
}(jQuery, Drupal, once));

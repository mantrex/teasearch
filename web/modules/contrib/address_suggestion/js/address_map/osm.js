(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_osm = {
    attach: function (context, settings) {
      $(once('map', '.map.osm', context)).each(function () {
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
        const map = L.map(id).setView([data.lat, data.lon], data.zoom);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        if (settings.address_map?.[id].points?.length > 0) {
          settings.address_map[id].points.forEach(function (point) {
            let marker = L.marker([point.lat, point.lon]).addTo(map);
            if (point.address) {
              marker.bindPopup(point.address.replace(/\n/g, '<br/>'));
            }
          });
        } else {
          let marker = L.marker([data.lat, data.lon]).addTo(map);
          if (data.address) {
            marker.bindPopup(data.address.replace(/\n/g, '<br/>'));
          }
        }
      });

    },
  }
}(jQuery, Drupal, once));

(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_tomtom = {
    attach: function (context, settings) {
      $(once('map', '.map.tomtom', context)).each(function () {
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
        let center = [data.lon, data.lat]
        let map = tt.map({
          key: settings.address_map?.[id].api_key,
          container: id,
          zoom: data.zoom,
          center: center,
        });
        map.addControl(new tt.FullscreenControl());
        map.addControl(new tt.NavigationControl());

        if (settings.address_map?.[id].points?.length > 0) {
          settings.address_map[id].points.forEach(function (point) {
            let marker = new tt.Marker().setLngLat([point.lon, point.lat]).addTo(map);
            if (point.address) {
              let popup = new tt.Popup().setHTML(point.address.replace(/\n/g, '<br/>'));
              marker.setPopup(popup).togglePopup();
            }
          });
        } else {
          let marker = new tt.Marker().setLngLat(center).addTo(map);
          if (data.address) {
            let popup = new tt.Popup().setHTML(data.address.replace(/\n/g, '<br/>'));
            marker.setPopup(popup).togglePopup();
          }
        }
      });

    },
  }
}(jQuery, Drupal, once));

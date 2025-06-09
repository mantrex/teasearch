(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_here = {
    attach: function (context, settings) {
      $(once('map', '.map.here', context)).each(function () {
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
        let platform = new H.service.Platform({
          'apikey': settings.address_map?.[id].api_key
        });
        let defaultLayers = platform.createDefaultLayers();
        defaultLayers.vector.normal.map.getProvider().setStyle(
          new H.map.Style('https://js.api.here.com/v3/3.1/styles/omv/normal.day.yaml')
        );
        let center = {lat: data.lat, lng: data.lon};
        if(data.address) {
          center.address = data.address;
        }
        let map = new H.Map(
          document.getElementById(id),
          defaultLayers.vector.normal.map,
          {
            zoom: data.zoom,
            center: center,
            pixelRatio: window.devicePixelRatio || 1,
          }
        );
        new H.mapevents.Behavior(new H.mapevents.MapEvents(map));
        const ui = H.ui.UI.createDefault(map, defaultLayers);
        const group = new H.map.Group();
        map.addObject(group);
        addMarkersAndBubbles(group, map, ui, settings.address_map?.[id]?.points || center);
        window.addEventListener('resize', () => map.getViewPort().resize());
      });

      function addMarkerToGroup(group, coordinate, html) {
        let marker = new H.map.Marker(coordinate);
        if(html) {
          marker.setData(html);
        }
        group.addObject(marker);
      }

      function addMarkersAndBubbles(group, map, ui, points) {
        group.addEventListener('tap', function (evt) {
          if (evt.target instanceof H.map.Marker) {
            let bubble = new H.ui.InfoBubble(evt.target.getGeometry(), {
              content: evt.target.getData()
            });
            ui.addBubble(bubble);
          }
        }, false);
        points.forEach(function(point) {
          const coordinate = {lat: point.lat, lng: point.lon};
          let html = point.address ? point.address.replace(/\n/g, '<br/>') : false;
          addMarkerToGroup(group, coordinate, html);
        });
      }
    },
  }
}(jQuery, Drupal, once));

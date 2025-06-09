(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_arcgis = {
    attach: function (context, settings) {
      $(once('map', '.map.arcgis', context)).each(function () {
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
        let center = [data.lon, data.lat];

        require(
          ["esri/Map",
            "esri/views/MapView",
            "esri/Graphic",
            "esri/layers/GraphicsLayer"
          ],
          (Map, MapView, Graphic, GraphicsLayer) => {
            const map = new Map({
              basemap: "topo-vector"
            });
            const view = new MapView({
              container: id,
              map: map,
              zoom: data.zoom,
              center: center
            });

            let graphicsLayer = new GraphicsLayer();

            map.add(graphicsLayer);
            let point = {type: "point", longitude: data.lon, latitude: data.lat};
            let simpleMarkerSymbol = {type: "simple-marker", color: [226, 119, 40], outline: {color: [255, 255, 255]}};
            let graphic = {geometry: point, symbol: simpleMarkerSymbol};
            if (data.address) {
              graphic.popupTemplate = {
                content: data.address.replace(/\n/g, '<br/>')
              };
            }
            let pointGraphic = new Graphic(graphic);
            graphicsLayer.add(pointGraphic);


          }
        );
      });

    },
  }
}(jQuery, Drupal, once));

(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_widget = {
    attach: function (context, settings) {

      $(once('initiate-autocomplete', 'input.address-suggestion-widget', context)).each(function () {
        const form_page = $(this).closest('form');
        let ui_autocomplete = $(this).data('ui-autocomplete');
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
          }
        }
      });
    }
  }
}(jQuery, Drupal, once));

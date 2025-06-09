(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion = {
    attach: function (context, settings) {

      $(once('initiate-autocomplete', 'input.address-suggestion', context)).each(function () {
        const formWrapper = $(this).closest('.js-form-wrapper');
        const formPage = formWrapper.closest('form');
        const inputAutocomplete = $(this).attr('role', 'presentation');
        const uiAutocomplete = inputAutocomplete.data('ui-autocomplete');
        const isHide = inputAutocomplete.data('hide');

        uiAutocomplete.options.select = function (event, ui) {
          event.preventDefault();

          formWrapper.find('input.address-line1').val(ui.item.street_name);
          formWrapper.find('input.address-line2').val(ui.item.district);

          if (ui.item.name && ui.item.name !== '') {
            formWrapper.find('input.organization').val(ui.item.name);
          }

          formWrapper.find('input.postal-code').val(ui.item.zip_code);
          formWrapper.find('input.locality').val(ui.item.town_name);
          ui.item.label = ui.item.label.substring(0, 128);

          if (typeof ui.item.state !== "undefined") {
            ui.item.state = ui.item.state.substring(0, 128);
            formWrapper.find('select.administrative-area').val(ui.item.state);
          } else if (typeof ui.item.administrative_area !== "undefined") {
            formWrapper.find('select.administrative-area').val(ui.item.administrative_area);
          }

          if (formWrapper.find('input.postal-code').length === 0 && formWrapper.find('input.locality').length === 0) {
            formWrapper.find('input.address-line1').val(ui.item.label);
          }

          let administrative = formWrapper.find('.administrative-area');
          if (administrative.length && !administrative.val()) {
            if (administrative.is('select') && administrative.find('option[value="' + ui.item.administrative_area + '"]').length === 0) {
              let newOption = '<option value="' + ui.item.administrative_area + '">' + ui.item.administrative_area + '</option>';
              administrative.append(newOption);
            }
            administrative.val(ui.item.administrative_area);
          }

          if ("location_field" in settings.address_suggestion && 'location' in ui.item) {
            let locationField = settings.address_suggestion.location_field;
            let typeField = settings.address_suggestion.type_field;
            let latitude = ui.item.location.latitude;
            let longitude = ui.item.location.longitude;

            function setLocationValues() {
              if (typeField === 'geolocation') {
                formPage.find("input[name*='" + locationField + "[0][lat]']").val(latitude);
                formPage.find("input[name*='" + locationField + "[0][lng]']").val(longitude);
              }

              if (typeField === 'geofield') {
                formPage.find("input[name*='" + locationField + "[0][value][lat]']").val(latitude);
                formPage.find("input[name*='" + locationField + "[0][value][lon]']").val(longitude);
              }
            }

            setLocationValues();
          }

          formWrapper.find('input.address-line').val(ui.item.label);
          formWrapper.find('select.country').val(ui.item.country_code.toUpperCase());

          if (isHide) {
            if (administrative.val() === '') {
              formWrapper.find('.administrative-area').remove();
            }

            let country = ui.item.country_code.toUpperCase();
            formWrapper.find('input.country').val(country);
          }
        };
      });
    }
  };
}(jQuery, Drupal, once));

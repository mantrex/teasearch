(function ($, Drupal, once) {
  Drupal.behaviors.address_suggestion_map_google = {
    attach: function (context, settings) {
      $(once('map', '.map.google', context)).each(function () {
        let data = $(this).data();
        let id = $(this).attr('id');
        if (data.height) {
          $(this).height(data.height);
        }
        if (data.width == '') {
          data.width = '100%';
        }
        $(this).width(data.width);
        let langCode = settings.path.currentLanguage;
        let srcMap = 'https://maps.google.com/maps?t=&z=14&ie=UTF8&iwloc=B&output=embed&hl=' + langCode + '&q=' + data.address;
        let map = `<iframe src="${srcMap}" class="i-address-suggestion" width="${data.width}" height="${data.height}" allowfullscreen=""
    loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>`;
        $(this).append(map)
      });

    },
  }
}(jQuery, Drupal, once));

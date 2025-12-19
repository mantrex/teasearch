/**
 * @file
 * Sincronizza il select della ricerca globale con il content type corrente.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.globalSearchSync = {
    attach: function (context, settings) {
      // Ottieni il content type dalla pagina
      const contentType = this.getContentTypeFromPage();

      if (contentType) {
        // Aggiorna il select nella barra di ricerca globale
        const selector = once('global-search-select', '.content-type-dropdown', context);

        selector.forEach(function (select) {
          if (select.value !== contentType) {
            select.value = contentType;
          }
        });
      }
    },

    getContentTypeFromPage: function () {
      // Try 1: URL parameter
      const urlParams = new URLSearchParams(window.location.search);
      const urlContentType = urlParams.get('content_type');
      if (urlContentType && urlContentType !== 'all') {
        return urlContentType;
      }

      // Try 2: Path-based routes (e.g., /primary_sources, /videos, etc.)
      const pathMappings = {
        '/primary_sources': 'texts',
        '/reference_materials': 'essentials',
        '/videos': 'video',
        '/images': 'images',
        '/people': 'people',
        '/bibliography': 'bibliography'
      };

      const currentPath = window.location.pathname;
      for (const [path, contentType] of Object.entries(pathMappings)) {
        if (currentPath.includes(path)) {
          return contentType;
        }
      }

      // Try 3: Data attribute from filters wrapper
      const filtersWrapper = document.querySelector('.teasearch-filters[data-content-type]');
      if (filtersWrapper) {
        const dataContentType = filtersWrapper.getAttribute('data-content-type');
        if (dataContentType && dataContentType !== 'all') {
          return dataContentType;
        }
      }

      return null;
    }
  };

})(Drupal, once);
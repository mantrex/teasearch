/**
 * @file
 * teasearch-sort.js
 *
 * Gestisce il cambio di ordinamento tramite il dropdown sort.
 * Aggiorna il parametro ?sort= nella URL preservando tutti gli altri
 * parametri attivi (filtri, pagina, per_page, ricerca).
 *
 * Comportamento:
 * - Al cambio della select, ricarica la pagina con ?sort=<key>&page=0
 * - Preserva tutti gli altri query params (filtri, q, content_type, per_page)
 * - Resetta sempre page=0 al cambio sort
 */
(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.teasearchSort = {
    attach: function (context) {
      const selects = once("teasearch-sort", ".sort-select", context);

      selects.forEach(function (select) {
        select.addEventListener("change", function () {
          const selectedSort = this.value;
          const isFreeSearch = this.dataset.isFreeSearch === "1";

          // Leggi URL corrente e preserva tutti i query params
          const url = new URL(window.location.href);
          const params = url.searchParams;

          // Imposta il nuovo sort e resetta la pagina
          params.set("sort", selectedSort);
          params.set("page", "0");

          // Naviga
          window.location.href = url.toString();
        });
      });
    },
  };
})(Drupal, once);

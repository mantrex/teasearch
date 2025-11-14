/**
 * @file
 * Comportamento sidebar per template info (Drupal 11, senza import).
 */
(function (Drupal) {
  "use strict";

  Drupal.behaviors.infoPageSidebar = {
    attach(context) {
      // Seleziona #info-sidebar una sola volta per questo context.
      once("info-sidebar", "#info-sidebar", context).forEach((sidebar) => {
        sidebar.querySelectorAll(".info-nav__link").forEach((link) => {
          link.addEventListener("click", (e) => {
            const href = link.getAttribute("href");
            if (href && href.startsWith("#")) {
              e.preventDefault();
              const target = document.querySelector(href);
              if (target) {
                const y =
                  target.getBoundingClientRect().top + window.pageYOffset - 100;
                window.scrollTo({ top: y, behavior: "smooth" });
              }
            }
          });
        });
      });
    },
  };
})(Drupal);

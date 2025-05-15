(function (Drupal) {
  Drupal.behaviors.teasearchDetailsState = {
    attach: function (context) {
      // Seleziono tutti i details nella sidebar
      var detailsList = context.querySelectorAll(".teasearch-sidebar details");
      detailsList.forEach(function (details) {
        // Ogni details ha un data-name che coincide con il field
        var name = details.getAttribute("data-name");
        if (!name) {
          // fallback: uso l'indice o un altro attributo
          name = details.querySelector("summary").textContent.trim();
        }

        // All'attacco, controllo sessionStorage
        var key = "teasearch_open_" + name;
        if (sessionStorage.getItem(key) === "1") {
          details.open = true;
        }

        // Aggiungo evento sul toggle
        details.addEventListener("toggle", function () {
          if (details.open) {
            sessionStorage.setItem(key, "1");
          } else {
            sessionStorage.removeItem(key);
          }
        });
      });
    },
  };
})(Drupal);

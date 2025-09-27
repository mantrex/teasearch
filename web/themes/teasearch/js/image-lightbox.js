(function (Drupal) {
  "use strict";

  /**
   * Teasearch Image Lightbox
   * Libreria riutilizzabile per popup delle immagini
   */
  Drupal.behaviors.teasearchImageLightbox = {
    attach: function (context, settings) {
      // Inizializza lightbox per tutti gli elementi con data-teasearch-lightbox
      const lightboxContainers = context.querySelectorAll(
        "[data-teasearch-lightbox]",
      );

      lightboxContainers.forEach(function (container) {
        if (container.dataset.lightboxInitialized) {
          return; // Già inizializzato
        }

        const img = container.querySelector("img");
        if (!img) return;

        container.addEventListener("click", function (e) {
          e.preventDefault();
          openLightbox(img.src, img.alt || "");
        });

        container.dataset.lightboxInitialized = "true";
      });
    },
  };

  /**
   * Apre il lightbox con l'immagine
   */
  function openLightbox(imageSrc, imageAlt) {
    // Crea overlay se non esiste
    let overlay = document.querySelector(".teasearch-lightbox-overlay");
    if (!overlay) {
      overlay = createLightboxOverlay();
      document.body.appendChild(overlay);
    }

    // Aggiorna immagine
    const img = overlay.querySelector(".teasearch-lightbox-image");
    img.src = imageSrc;
    img.alt = imageAlt;

    // Mostra lightbox
    document.body.style.overflow = "hidden";
    overlay.classList.add("active");
  }

  /**
   * Chiude il lightbox
   */
  function closeLightbox() {
    const overlay = document.querySelector(".teasearch-lightbox-overlay");
    if (overlay) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";
    }
  }

  /**
   * Crea la struttura HTML del lightbox
   */
  function createLightboxOverlay() {
    const overlay = document.createElement("div");
    overlay.className = "teasearch-lightbox-overlay";

    overlay.innerHTML = `
      <div class="teasearch-lightbox-content">
        <img class="teasearch-lightbox-image" src="" alt="">
        <button class="teasearch-lightbox-close" type="button">×</button>
      </div>
    `;

    // Event listeners
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) {
        closeLightbox();
      }
    });

    const closeBtn = overlay.querySelector(".teasearch-lightbox-close");
    closeBtn.addEventListener("click", closeLightbox);

    // Chiudi con ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closeLightbox();
      }
    });

    return overlay;
  }
})(Drupal);

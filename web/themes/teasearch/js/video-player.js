(function (Drupal) {
  "use strict";

  /**
   * Teasearch Video Player
   * Gestisce video YouTube inline e popup
   */
  Drupal.behaviors.teasearchVideoPlayer = {
    attach: function (context, settings) {
      // ======================================================================
      // INLINE MODE - Sostituisce thumbnail con iframe
      // ======================================================================
      const inlineThumbnails = context.querySelectorAll(
        ".detail-video-inline .detail-video-thumbnail[data-video-id]",
      );

      inlineThumbnails.forEach(function (thumbnail) {
        if (thumbnail.dataset.videoInitialized) {
          return; // Già inizializzato
        }

        thumbnail.addEventListener("click", function (e) {
          e.preventDefault();
          const videoId = thumbnail.dataset.videoId;
          const youtubeCode = thumbnail.dataset.youtubeCode;

          if (!youtubeCode) {
            console.error("YouTube code not found");
            return;
          }

          // Trova il container e il player
          const container = document.getElementById(videoId);
          if (!container) {
            console.error("Video container not found:", videoId);
            return;
          }

          const player = container.querySelector(".detail-video-player");
          if (!player) {
            console.error("Video player not found");
            return;
          }

          // Crea iframe YouTube
          const iframe = document.createElement("iframe");
          iframe.src = `https://www.youtube.com/embed/${youtubeCode}?autoplay=1`;
          iframe.allow =
            "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
          iframe.allowFullscreen = true;
          iframe.title = "YouTube video player";

          // Sostituisci thumbnail con iframe
          thumbnail.style.display = "none";
          player.style.display = "block";
          player.innerHTML = "";
          player.appendChild(iframe);
        });

        thumbnail.dataset.videoInitialized = "true";
      });

      // ======================================================================
      // POPUP MODE - Apre modal con iframe
      // ======================================================================
      const popupThumbnails = context.querySelectorAll(
        ".detail-video-popup .detail-video-thumbnail[data-video-popup]",
      );

      popupThumbnails.forEach(function (thumbnail) {
        if (thumbnail.dataset.videoPopupInitialized) {
          return; // Già inizializzato
        }

        thumbnail.addEventListener("click", function (e) {
          e.preventDefault();
          const youtubeCode = thumbnail.dataset.youtubeCode;

          if (!youtubeCode) {
            console.error("YouTube code not found");
            return;
          }

          openVideoModal(youtubeCode);
        });

        thumbnail.dataset.videoPopupInitialized = "true";
      });
    },
  };

  /**
   * Apre il modal con il video YouTube
   */
  function openVideoModal(youtubeCode) {
    // Crea overlay se non esiste
    let overlay = document.querySelector(".teasearch-video-modal-overlay");
    if (!overlay) {
      overlay = createVideoModalOverlay();
      document.body.appendChild(overlay);
    }

    // Crea iframe YouTube
    const content = overlay.querySelector(".teasearch-video-modal-content");
    const iframe = document.createElement("iframe");
    iframe.src = `https://www.youtube.com/embed/${youtubeCode}?autoplay=1`;
    iframe.allow =
      "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
    iframe.allowFullscreen = true;
    iframe.title = "YouTube video player";

    // Svuota e aggiungi iframe
    content.innerHTML = "";
    content.appendChild(iframe);

    // Mostra modal
    document.body.style.overflow = "hidden";
    overlay.classList.add("active");
  }

  /**
   * Chiude il modal video
   */
  function closeVideoModal() {
    const overlay = document.querySelector(".teasearch-video-modal-overlay");
    if (overlay) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";

      // Rimuovi iframe per fermare il video
      setTimeout(() => {
        const content = overlay.querySelector(".teasearch-video-modal-content");
        if (content) {
          content.innerHTML = "";
        }
      }, 300); // Aspetta la fine della transizione
    }
  }

  /**
   * Crea la struttura HTML del modal video
   */
  function createVideoModalOverlay() {
    const overlay = document.createElement("div");
    overlay.className = "teasearch-video-modal-overlay";

    overlay.innerHTML = `
      <div class="teasearch-video-modal-content">
        <!-- iframe will be injected here -->
      </div>
      <button class="teasearch-video-modal-close" type="button" aria-label="Close video">×</button>
    `;

    // Event listeners
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) {
        closeVideoModal();
      }
    });

    const closeBtn = overlay.querySelector(".teasearch-video-modal-close");
    closeBtn.addEventListener("click", closeVideoModal);

    // Chiudi con ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closeVideoModal();
      }
    });

    return overlay;
  }
})(Drupal);

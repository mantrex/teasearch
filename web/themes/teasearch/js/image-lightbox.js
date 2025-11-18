(function (Drupal) {
  "use strict";

  console.log("🔍 TEASEARCH LIGHTBOX ADVANCED - VERSION 2.0 LOADED");

  /**
   * Teasearch Image Lightbox - Advanced Version
   * Con scroll, zoom, drag, e controlli completi
   */
  Drupal.behaviors.teasearchImageLightbox = {
    attach: function (context, settings) {
      console.log("🎯 Lightbox behavior attached");

      // Inizializza lightbox per tutti gli elementi con data-teasearch-lightbox
      const lightboxContainers = context.querySelectorAll(
        "[data-teasearch-lightbox]",
      );

      console.log(`📸 Found ${lightboxContainers.length} lightbox containers`);

      lightboxContainers.forEach(function (container) {
        if (container.dataset.lightboxInitialized) {
          return;
        }

        const img = container.querySelector("img");
        if (!img) return;

        container.addEventListener("click", function (e) {
          e.preventDefault();
          console.log("🖱️ Lightbox clicked!");

          // Usa data-large-src se presente, altrimenti usa img.src
          const imageUrl = container.dataset.largeSrc || img.src;
          const imageAlt = img.alt || "";

          console.log("📷 Opening image:", imageUrl);
          openLightbox(imageUrl, imageAlt);
        });

        container.dataset.lightboxInitialized = "true";
      });
    },
  };

  // ============================================================================
  // STATO GLOBALE LIGHTBOX
  // ============================================================================
  let lightboxState = {
    scale: 1,
    minScale: 0.5,
    maxScale: 5,
    step: 0.25,
    isDragging: false,
    startX: 0,
    startY: 0,
    scrollLeft: 0,
    scrollTop: 0,
  };

  /**
   * Apre il lightbox con l'immagine
   */
  function openLightbox(imageSrc, imageAlt) {
    console.log("✨ Opening lightbox with advanced controls");

    // Crea overlay se non esiste
    let overlay = document.querySelector(".teasearch-lightbox-overlay");
    if (!overlay) {
      console.log("🏗️ Creating new lightbox overlay");
      overlay = createLightboxOverlay();
      document.body.appendChild(overlay);
    }

    // Reset stato
    lightboxState.scale = 1;
    lightboxState.isDragging = false;

    // Mostra loading
    const loading = overlay.querySelector(".teasearch-lightbox-loading");
    loading.style.display = "block";

    // Nascondi immagine durante caricamento
    const wrapper = overlay.querySelector(".teasearch-lightbox-image-wrapper");
    wrapper.style.opacity = "0";

    // Aggiorna immagine
    const img = overlay.querySelector(".teasearch-lightbox-image");
    img.onload = function () {
      console.log("✅ Image loaded successfully");
      loading.style.display = "none";
      wrapper.style.opacity = "1";
      updateZoomLevel();
      checkResetButton();
    };
    img.src = imageSrc;
    img.alt = imageAlt;

    // Reset transform
    wrapper.style.transform = "scale(1)";

    // Mostra lightbox
    document.body.style.overflow = "hidden";
    overlay.classList.add("active");

    // Centra scroll
    const content = overlay.querySelector(".teasearch-lightbox-content");
    setTimeout(() => {
      content.scrollLeft = (content.scrollWidth - content.clientWidth) / 2;
      content.scrollTop = (content.scrollHeight - content.clientHeight) / 2;
    }, 50);
  }

  /**
   * Chiude il lightbox
   */
  function closeLightbox() {
    console.log("❌ Closing lightbox");
    const overlay = document.querySelector(".teasearch-lightbox-overlay");
    if (overlay) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";

      // Reset
      lightboxState.scale = 1;
      lightboxState.isDragging = false;
    }
  }

  /**
   * Zoom In
   */
  function zoomIn() {
    const wrapper = document.querySelector(".teasearch-lightbox-image-wrapper");
    if (!wrapper) return;

    lightboxState.scale = Math.min(
      lightboxState.scale + lightboxState.step,
      lightboxState.maxScale,
    );
    console.log("🔍 Zoom in:", Math.round(lightboxState.scale * 100) + "%");
    wrapper.style.transform = `scale(${lightboxState.scale})`;
    updateZoomLevel();
    checkResetButton();
  }

  /**
   * Zoom Out
   */
  function zoomOut() {
    const wrapper = document.querySelector(".teasearch-lightbox-image-wrapper");
    if (!wrapper) return;

    lightboxState.scale = Math.max(
      lightboxState.scale - lightboxState.step,
      lightboxState.minScale,
    );
    console.log("🔍 Zoom out:", Math.round(lightboxState.scale * 100) + "%");
    wrapper.style.transform = `scale(${lightboxState.scale})`;
    updateZoomLevel();
    checkResetButton();
  }

  /**
   * Reset Zoom
   */
  function resetZoom() {
    const wrapper = document.querySelector(".teasearch-lightbox-image-wrapper");
    if (!wrapper) return;

    console.log("🔄 Reset zoom to 100%");
    lightboxState.scale = 1;
    wrapper.style.transform = "scale(1)";
    updateZoomLevel();
    checkResetButton();

    // Centra scroll
    const content = document.querySelector(".teasearch-lightbox-content");
    content.scrollLeft = (content.scrollWidth - content.clientWidth) / 2;
    content.scrollTop = (content.scrollHeight - content.clientHeight) / 2;
  }

  /**
   * Aggiorna indicatore zoom
   */
  function updateZoomLevel() {
    const indicator = document.querySelector(".teasearch-lightbox-zoom-level");
    if (!indicator) return;

    const percentage = Math.round(lightboxState.scale * 100);
    indicator.textContent = `${percentage}%`;

    // Mostra temporaneamente
    indicator.classList.add("visible");
    clearTimeout(indicator.hideTimeout);
    indicator.hideTimeout = setTimeout(() => {
      indicator.classList.remove("visible");
    }, 1500);
  }

  /**
   * Mostra/nascondi reset button
   */
  function checkResetButton() {
    const resetBtn = document.querySelector(".teasearch-lightbox-reset-btn");
    if (!resetBtn) return;

    if (lightboxState.scale !== 1) {
      resetBtn.classList.add("visible");
    } else {
      resetBtn.classList.remove("visible");
    }
  }

  /**
   * Crea la struttura HTML del lightbox
   */
  function createLightboxOverlay() {
    console.log("🎨 Creating advanced lightbox with zoom controls");

    const overlay = document.createElement("div");
    overlay.className = "teasearch-lightbox-overlay";

    overlay.innerHTML = `
      <div class="teasearch-lightbox-content">
        <div class="teasearch-lightbox-image-wrapper">
          <img class="teasearch-lightbox-image" src="" alt="">
        </div>
      </div>
      
      <button class="teasearch-lightbox-close" type="button" aria-label="Close">×</button>
      
      <div class="teasearch-lightbox-zoom-controls">
        <button class="teasearch-lightbox-zoom-btn zoom-out" type="button" aria-label="Zoom out">−</button>
        <button class="teasearch-lightbox-zoom-btn zoom-in" type="button" aria-label="Zoom in">+</button>
      </div>
      
      <div class="teasearch-lightbox-zoom-level">100%</div>
      
      <button class="teasearch-lightbox-reset-btn" type="button">Reset Zoom</button>
      
      <div class="teasearch-lightbox-loading"></div>
    `;

    // ========================================================================
    // EVENT LISTENERS
    // ========================================================================

    // Close button
    const closeBtn = overlay.querySelector(".teasearch-lightbox-close");
    closeBtn.addEventListener("click", closeLightbox);

    // Click su overlay (ma non sull'immagine)
    overlay.addEventListener("click", function (e) {
      if (
        e.target === overlay ||
        e.target.classList.contains("teasearch-lightbox-content")
      ) {
        closeLightbox();
      }
    });

    // Zoom buttons
    overlay.querySelector(".zoom-in").addEventListener("click", function () {
      console.log("➕ Zoom in button clicked");
      zoomIn();
    });
    overlay.querySelector(".zoom-out").addEventListener("click", function () {
      console.log("➖ Zoom out button clicked");
      zoomOut();
    });

    // Reset button
    overlay
      .querySelector(".teasearch-lightbox-reset-btn")
      .addEventListener("click", resetZoom);

    // Chiudi con ESC
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        closeLightbox();
      }
    });

    // Zoom con rotellina mouse
    const content = overlay.querySelector(".teasearch-lightbox-content");
    content.addEventListener(
      "wheel",
      function (e) {
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault();
          if (e.deltaY < 0) {
            zoomIn();
          } else {
            zoomOut();
          }
        }
      },
      { passive: false },
    );

    // ========================================================================
    // DRAG TO SCROLL
    // ========================================================================
    const wrapper = overlay.querySelector(".teasearch-lightbox-image-wrapper");

    wrapper.addEventListener("mousedown", function (e) {
      if (lightboxState.scale <= 1) return; // Drag solo se zoomato

      lightboxState.isDragging = true;
      wrapper.classList.add("dragging");
      lightboxState.startX = e.pageX - content.offsetLeft;
      lightboxState.startY = e.pageY - content.offsetTop;
      lightboxState.scrollLeft = content.scrollLeft;
      lightboxState.scrollTop = content.scrollTop;
    });

    document.addEventListener("mousemove", function (e) {
      if (!lightboxState.isDragging) return;
      e.preventDefault();

      const x = e.pageX - content.offsetLeft;
      const y = e.pageY - content.offsetTop;
      const walkX = (x - lightboxState.startX) * 2;
      const walkY = (y - lightboxState.startY) * 2;
      content.scrollLeft = lightboxState.scrollLeft - walkX;
      content.scrollTop = lightboxState.scrollTop - walkY;
    });

    document.addEventListener("mouseup", function () {
      lightboxState.isDragging = false;
      wrapper.classList.remove("dragging");
    });

    // ========================================================================
    // TOUCH/PINCH ZOOM (Mobile)
    // ========================================================================
    let touchDistance = 0;

    wrapper.addEventListener(
      "touchstart",
      function (e) {
        if (e.touches.length === 2) {
          e.preventDefault();
          touchDistance = getTouchDistance(e.touches);
        }
      },
      { passive: false },
    );

    wrapper.addEventListener(
      "touchmove",
      function (e) {
        if (e.touches.length === 2) {
          e.preventDefault();
          const newDistance = getTouchDistance(e.touches);
          const scale = newDistance / touchDistance;

          lightboxState.scale = Math.max(
            lightboxState.minScale,
            Math.min(lightboxState.maxScale, lightboxState.scale * scale),
          );

          wrapper.style.transform = `scale(${lightboxState.scale})`;
          updateZoomLevel();
          checkResetButton();

          touchDistance = newDistance;
        }
      },
      { passive: false },
    );

    console.log("✅ Lightbox overlay created with all controls");
    return overlay;
  }

  /**
   * Helper: Calcola distanza tra due touch points
   */
  function getTouchDistance(touches) {
    const dx = touches[0].pageX - touches[1].pageX;
    const dy = touches[0].pageY - touches[1].pageY;
    return Math.sqrt(dx * dx + dy * dy);
  }
})(Drupal);

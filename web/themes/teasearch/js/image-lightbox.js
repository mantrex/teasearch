(function (Drupal) {
  "use strict";

  console.log("🔍 TEASEARCH LIGHTBOX ADVANCED - VERSION 3.0 LOADED");

  /**
   * Teasearch Image Lightbox - Advanced Version
   * Con scroll, zoom, drag, e controlli completi
   */
  Drupal.behaviors.teasearchImageLightbox = {
    attach: function (context, settings) {
      console.log("🎯 Lightbox behavior attached");

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
    maxScale: 20,
    step: 0.25,
    isDragging: false,
    startX: 0,
    startY: 0,
    translateX: 0,
    translateY: 0,
    startTranslateX: 0,
    startTranslateY: 0,
  };

  const PAN_STEP_BASE = 40; // pixel per pressione freccia



  /**
   * Apre il lightbox con l'immagine
   */
  function openLightbox(imageSrc, imageAlt) {
    console.log("✨ Opening lightbox with advanced controls");

    let overlay = document.querySelector(".teasearch-lightbox-overlay");
    if (!overlay) {
      console.log("🏗️ Creating new lightbox overlay");
      overlay = createLightboxOverlay();
      document.body.appendChild(overlay);
    }

    // Reset stato
    lightboxState.scale = 1;
    lightboxState.isDragging = false;
    lightboxState.translateX = 0;
    lightboxState.translateY = 0;

    // Mostra loading
    const loading = overlay.querySelector(".teasearch-lightbox-loading");
    loading.style.display = "block";

    // Nascondi immagine durante caricamento
    const wrapper = overlay.querySelector(".teasearch-lightbox-image-wrapper");
    const img = overlay.querySelector(".teasearch-lightbox-image");

    wrapper.style.opacity = "0";

    // Aggiorna immagine
    img.onload = function () {
      console.log("✅ Image loaded successfully");
      loading.style.display = "none";

      // Calcola dimensioni wrapper per permettere scroll completo
      updateWrapperSize();

      wrapper.style.opacity = "1";
      updateZoomLevel();
      checkResetButton();
    };

    img.src = imageSrc;
    img.alt = imageAlt;

    // Reset transform
    img.style.transform = "translate(0, 0) scale(1)";

    // Mostra lightbox
    document.body.style.overflow = "hidden";
    overlay.classList.add("active");
  }

  /**
   * Aggiorna dimensioni del wrapper in base al maxScale
   * QUESTO È IL TRUCCO: il wrapper deve essere abbastanza grande
   * da contenere l'immagine al massimo zoom
   */
  function updateWrapperSize() {
    const wrapper = document.querySelector(".teasearch-lightbox-image-wrapper");
    const img = document.querySelector(".teasearch-lightbox-image");
    if (!wrapper || !img) return;

    // Aspetta che l'immagine sia caricata
    if (!img.complete) return;

    // Dimensioni dell'immagine visualizzata (dopo max-width/max-height)
    const displayedWidth = img.offsetWidth;
    const displayedHeight = img.offsetHeight;

    // Usa lo zoom corrente, non il maxScale
    const currentScale = lightboxState.scale || 1;

    // Wrapper dimensionato sull’ingrandimento attuale
    const wrapperWidth = displayedWidth * currentScale;
    const wrapperHeight = displayedHeight * currentScale;

    wrapper.style.width = wrapperWidth + "px";
    wrapper.style.height = wrapperHeight + "px";

    console.log(`📐 Wrapper size (scale ${currentScale}): ${wrapperWidth}x${wrapperHeight}`);
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



  function updateTransform() {
    const img = document.querySelector(".teasearch-lightbox-image");
    if (!img) return;

    img.style.transform = `translate(${lightboxState.translateX}px, ${lightboxState.translateY}px) scale(${lightboxState.scale})`;
  }

  function centerImage() {
    const content = document.querySelector(".teasearch-lightbox-content");
    if (!content) return;

    // se il container ha overflow:auto, riportiamo lo scroll al centro
    content.scrollLeft = (content.scrollWidth - content.clientWidth) / 2;
    content.scrollTop = (content.scrollHeight - content.clientHeight) / 2;
  }

  /**
   * Zoom In
   */
  function zoomIn() {
    const img = document.querySelector(".teasearch-lightbox-image");
    if (!img) return;

    lightboxState.scale = Math.min(
      lightboxState.scale + lightboxState.step,
      lightboxState.maxScale,
    );
    console.log("🔍 Zoom in:", Math.round(lightboxState.scale * 100) + "%");

    img.style.transform = `scale(${lightboxState.scale})`;

    // 🔴 AGGIUNGI QUESTO
    updateTransform();
    updateZoomLevel();
    checkResetButton();
  }

  /**
   * Zoom Out
   */
  function zoomOut() {
    const img = document.querySelector(".teasearch-lightbox-image");
    if (!img) return;

    lightboxState.scale = Math.max(
      lightboxState.scale - lightboxState.step,
      lightboxState.minScale,
    );
    console.log("🔍 Zoom out:", Math.round(lightboxState.scale * 100) + "%");

    img.style.transform = `scale(${lightboxState.scale})`;

    // 🔴 AGGIUNGI QUESTO
    updateTransform();

    updateZoomLevel();
    checkResetButton();
  }

  /**
   * Reset Zoom
   */
  function resetZoom() {
    const img = document.querySelector(".teasearch-lightbox-image");
    if (!img) return;

    console.log("🔄 Reset zoom to 100% e recentra immagine");

    // Zoom default
    lightboxState.scale = 1;

    // Azzeriamo completamente il pan
    lightboxState.translateX = 0;
    lightboxState.translateY = 0;

    // Applica trasformazione (centrata, senza offset)
    updateTransform();

    // Ricentra anche lo scroll del contenitore (se overflow:auto)
    centerImage();

    updateZoomLevel();
    checkResetButton();
  }

  /**
   * Aggiorna indicatore zoom
   */
  function updateZoomLevel() {
    const indicator = document.querySelector(".teasearch-lightbox-zoom-level");
    if (!indicator) return;

    const percentage = Math.round(lightboxState.scale * 100);
    indicator.textContent = `${percentage}%`;

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

    // Click su overlay
    overlay.addEventListener("click", function (e) {
      if (
        e.target === overlay ||
        e.target.classList.contains("teasearch-lightbox-content")
      ) {
        closeLightbox();
      }
    });

    // Zoom buttons
    overlay.querySelector(".zoom-in").addEventListener("click", zoomIn);
    overlay.querySelector(".zoom-out").addEventListener("click", zoomOut);

    // Reset button
    overlay
      .querySelector(".teasearch-lightbox-reset-btn")
      .addEventListener("click", resetZoom);

    // ESC key
    // Tastiera: ESC per chiudere, frecce per muovere, +/- per zoom
    document.addEventListener("keydown", function (e) {
      if (!overlay.classList.contains("active")) {
        return;
      }

      // ESC -> chiudi
      if (e.key === "Escape") {
        closeLightbox();
        return;
      }

      const img = overlay.querySelector(".teasearch-lightbox-image");
      if (!img) return;

      let handled = false;

      // Calcoliamo lo step in funzione dello zoom,
      // così a 300% ti muovi più "veloce".
      const panStep = PAN_STEP_BASE * (lightboxState.scale || 1);

      switch (e.key) {
        case "ArrowUp":
          lightboxState.translateY += panStep;
          handled = true;
          break;
        case "ArrowDown":
          lightboxState.translateY -= panStep;
          handled = true;
          break;
        case "ArrowLeft":
          lightboxState.translateX += panStep;
          handled = true;
          break;
        case "ArrowRight":
          lightboxState.translateX -= panStep;
          handled = true;
          break;
        case "+":
        case "=": // tasto + senza shift su alcune tastiere
          zoomIn();
          handled = true;
          break;
        case "-":
          zoomOut();
          handled = true;
          break;
      }

      if (handled) {
        e.preventDefault(); // blocca lo scroll della pagina
        updateTransform();
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
      // drag solo se sei oltre il 100%
      if (lightboxState.scale <= 1) return;

      e.preventDefault();
      lightboxState.isDragging = true;
      wrapper.classList.add("dragging");

      lightboxState.startX = e.clientX;
      lightboxState.startY = e.clientY;
      lightboxState.startTranslateX = lightboxState.translateX;
      lightboxState.startTranslateY = lightboxState.translateY;
    });

    document.addEventListener("mousemove", function (e) {
      if (!lightboxState.isDragging) return;

      e.preventDefault();

      const dx = e.clientX - lightboxState.startX;
      const dy = e.clientY - lightboxState.startY;

      lightboxState.translateX = lightboxState.startTranslateX + dx;
      lightboxState.translateY = lightboxState.startTranslateY + dy;

      updateTransform();
    });

    document.addEventListener("mouseup", function () {
      if (!lightboxState.isDragging) return;
      lightboxState.isDragging = false;
      wrapper.classList.remove("dragging");
    });

    // ========================================================================
    // TOUCH/PINCH ZOOM (Mobile)
    // ========================================================================
    let touchDistance = 0;

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

          updateTransform();
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
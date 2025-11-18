(function ($, Drupal) {
  Drupal.behaviors.carouselInit = {
    attach: function (context, settings) {
      // Trova la wrapper solo se non è già processata
      const wrapper = document.getElementById("carousel-content");
      if (!wrapper || wrapper.classList.contains("loaded")) {
        return;
      }

      // Variabile per tracciare i dati del carousel
      let carouselData = [];

      // Recupera i dati dal tuo endpoint JSON
      fetch("carousel-data?_format=json")
        .then((response) => response.json())
        .then((data) => {
          carouselData = data; // Salva i dati per il tracking

          // Inserisce ogni elemento come slide
          data.forEach((item, index) => {
            const slide = document.createElement("div");
            slide.className = "swiper-slide";
            slide.setAttribute("data-nid", item.nid);
            slide.setAttribute("data-bundle", item.bundle);
            slide.innerHTML = `
              <div >
              <a class="plain" href="${item.url}">
              <div class="news-thumbnail" >
                <img src="${item.image}" alt="">
              </div>
              <div class="news-kicker">${item.label || item.bundle}</div>
              <div class="news-title">
                <div>${item.title}</div>
              </div>
              </a>
              </div>
            `;
            wrapper.appendChild(slide);
          });

          // Previene re-inizializzazioni
          wrapper.classList.add("loaded");

          // Inizializza lo Swiper
          const swiper = new Swiper(".swiper", {
            loop: true,
            slidesPerView: "auto",
            spaceBetween: 10,
            loop: data.length > 6,
            watchOverflow: true, // se poche slide, disabilita automaticamente
            slidesPerGroupAuto: true, // scorre di quanto sta a schermo
            autoplay: {
              delay: 5000,
              disableOnInteraction: false,
            },
            pagination: {
              el: ".swiper-pagination",
              clickable: true,
              dynamicBullets: true,
            },
            navigation: {
              nextEl: ".swiper-button-next",
              prevEl: ".swiper-button-prev",
            },
            breakpoints: {
              0: { slidesPerView: 1.3, spaceBetween: 8 }, // mobile piccolo
              320: { slidesPerView: 2, spaceBetween: 10 }, // 👈 da 640px in su: 3 card
              580: { slidesPerView: 3, spaceBetween: 10 }, // 👈 da 640px in su: 3 card
              640: { slidesPerView: 3, spaceBetween: 12 }, // 👈 da 640px in su: 3 card
              900: { slidesPerView: 3, spaceBetween: 12 }, // NEW → a 950px vedi 3 card, più grandi
              1100: { slidesPerView: 4, spaceBetween: 12 }, // 4 card solo sopra 1100px
              1280: { slidesPerView: 5, spaceBetween: 12 }, // 5 card su desktop ampio
            },
            // Callback per aggiornare il link "View All" quando cambia slide
            on: {
              slideChange: function () {
                updateViewAllLink(this, carouselData);
              },
              init: function () {
                updateViewAllLink(this, carouselData);
              },
            },
          });

          function updateViewAllLink(swiperInstance, data) {
            const viewAllBtn = document.getElementById("carousel-view-all");
            if (!viewAllBtn) return;

            try {
              // Ottieni l'URL base originale dal DOM (senza parametri)
              const originalUrl = viewAllBtn.href.split("?")[0];

              // Ottieni le slide attualmente visibili
              const visibleSlides = swiperInstance.slides.filter(
                (slide, index) => {
                  const slideProgress = swiperInstance.slides[index]
                    ? swiperInstance.slides[index].progress
                    : 1;
                  // Considera visibile una slide se è almeno parzialmente nell'area visibile
                  return slideProgress >= -1 && slideProgress <= 1;
                },
              );

              // Estrai gli ID delle slide visibili
              const visibleIds = [];
              visibleSlides.forEach((slide) => {
                const nid = slide.getAttribute("data-nid");
                if (nid && !visibleIds.includes(nid)) {
                  visibleIds.push(nid);
                }
              });

              // Se non riusciamo a ottenere le slide visibili, usa tutti gli elementi
              if (visibleIds.length === 0) {
                data.forEach((item) => {
                  if (item.nid && !visibleIds.includes(item.nid)) {
                    visibleIds.push(item.nid);
                  }
                });
              }

              // Aggiorna solo i parametri, mantenendo l'URL base originale
              const urlWithIds =
                visibleIds.length > 0
                  ? `${originalUrl}?ids=${visibleIds.join(",")}`
                  : originalUrl;

              viewAllBtn.href = urlWithIds;
            } catch (error) {
              console.warn(
                "Errore nell'aggiornamento del link View All:",
                error,
              );
              // Fallback: mantieni l'URL originale e aggiungi tutti gli ID
              const originalUrl = viewAllBtn.href.split("?")[0];
              const allIds = data.map((item) => item.nid).filter((nid) => nid);
              viewAllBtn.href =
                allIds.length > 0
                  ? `${originalUrl}?ids=${allIds.join(",")}`
                  : originalUrl;
            }
          }
        })
        .catch((error) => {
          console.error("Errore nel caricamento del carousel:", error);
        });
    },
  };
})(jQuery, Drupal);

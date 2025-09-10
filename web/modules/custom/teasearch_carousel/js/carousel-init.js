(function ($, Drupal) {
  Drupal.behaviors.carouselInit = {
    attach: function (context, settings) {
      // Trova la wrapper solo se non è già processata
      const wrapper = document.getElementById("carousel-content");
      if (!wrapper || wrapper.classList.contains("loaded")) {
        return;
      }

      // Recupera i dati dal tuo endpoint JSON
      fetch("carousel-data?_format=json")
        .then((response) => response.json())
        .then((data) => {
          // Inserisce ogni elemento come slide
          data.forEach((item) => {
            const slide = document.createElement("div");
            slide.className = "swiper-slide";
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
          new Swiper(".swiper", {
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
          });
        })
        .catch((error) => {
          console.error("Errore nel caricamento del carousel:", error);
        });
    },
  };
})(jQuery, Drupal);

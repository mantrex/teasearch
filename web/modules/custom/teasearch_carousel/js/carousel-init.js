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
            slidesPerView: 5,
            spaceBetween: 50,
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
              992: { slidesPerView: 5 },
              768: { slidesPerView: 4 },
              0: { slidesPerView: 2 },
            },
          });
        })
        .catch((error) => {
          console.error("Errore nel caricamento del carousel:", error);
        });
    },
  };
})(jQuery, Drupal);

(function ($, Drupal) {
  Drupal.behaviors.carouselInit = {
    attach: function (context, settings) {
      const wrapper = document.getElementById("carousel-content");
      if (!wrapper || wrapper.classList.contains("loaded")) {
        return;
      }

      let carouselData = [];

      fetch("carousel-data?_format=json")
        .then((response) => response.json())
        .then((data) => {
          carouselData = data;

          data.forEach((item, index) => {
            const slide = document.createElement("div");
            slide.className = "swiper-slide";
            slide.setAttribute("data-nid", item.nid);
            slide.setAttribute("data-bundle", item.bundle);
            slide.innerHTML = `
              <div>
              <a class="plain carousel-card-link" href="${item.url}">
              <div class="news-thumbnail">
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

          wrapper.classList.add("loaded");

          const swiperInstance = new Swiper(".swiper", {
            loop: data.length > 6,
            slidesPerView: "auto",
            spaceBetween: 10,
            watchOverflow: true,
            slidesPerGroupAuto: true,
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
              0: { slidesPerView: 1.3, spaceBetween: 8 },
              320: { slidesPerView: 2, spaceBetween: 10 },
              580: { slidesPerView: 3, spaceBetween: 10 },
              640: { slidesPerView: 3, spaceBetween: 12 },
              900: { slidesPerView: 3, spaceBetween: 12 },
              1100: { slidesPerView: 4, spaceBetween: 12 },
              1280: { slidesPerView: 5, spaceBetween: 12 },
            },
            on: {
              init: function () {
                updateViewAllLink(this, carouselData);
                setupAccessibility(this);
              },
              slideChange: function () {
                updateViewAllLink(this, carouselData);
                updateFocusability(this);
              },
            },
          });

          setupArrowNavigation(swiperInstance, wrapper);

        })
        .catch((error) => {
          console.error("Errore caricamento carousel:", error);
        });

      /**
       * Navigazione con frecce tastiera quando focus è su una card
       */
      function setupArrowNavigation(swiper, wrapperEl) {
        // Prendo lo swiper “giusto” relativo a questo carousel
        const swiperEl = wrapperEl.closest('.swiper') || swiper.el;

        let isNavigatingWithKeyboard = false;

        swiperEl.addEventListener('keydown', function (e) {
          const link = e.target.closest('a.carousel-card-link');
          if (!link) return;

          if (e.key === 'ArrowLeft') {
            e.preventDefault();
            isNavigatingWithKeyboard = true;
            swiper.slidePrev();
          } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            isNavigatingWithKeyboard = true;
            swiper.slideNext();
          }
        });

        swiper.on('slideChangeTransitionEnd', function () {
          if (!isNavigatingWithKeyboard) return;

          // meglio rifocalizzare la card ATTUALMENTE visibile del tuo swiper
          const focusable = swiper.el.querySelector(
            '.swiper-slide:not(.swiper-slide-duplicate).swiper-slide-visible a[tabindex="0"]'
          );
          if (focusable) focusable.focus();

          isNavigatingWithKeyboard = false;
        });
      }


      function setupAccessibility(swiper) {
        const navButtons = document.querySelectorAll('.swiper-button-prev, .swiper-button-next');
        const pagination = document.querySelector('.swiper-pagination');

        navButtons.forEach(btn => {
          btn.setAttribute('tabindex', '-1');
          btn.setAttribute('aria-hidden', 'true');
        });

        if (pagination) {
          pagination.setAttribute('aria-hidden', 'true');

          const updateBullets = () => {
            const bullets = document.querySelectorAll('.swiper-pagination-bullet');
            bullets.forEach(bullet => {
              bullet.setAttribute('tabindex', '-1');
              bullet.removeAttribute('role');
            });
          };

          updateBullets();
          swiper.on('slideChange', updateBullets);
        }

        updateFocusability(swiper);
      }

      function updateFocusability(swiper) {
        let firstRealVisible = null;

        swiper.slides.forEach((slide) => {
          const isDuplicate = slide.classList.contains('swiper-slide-duplicate');
          const isVisible = slide.classList.contains('swiper-slide-visible');

          if (isVisible && !isDuplicate && !firstRealVisible) {
            firstRealVisible = slide;
          }
        });

        swiper.slides.forEach((slide) => {
          const link = slide.querySelector('a');
          if (!link) return;

          const isDuplicate = slide.classList.contains('swiper-slide-duplicate');

          if (isDuplicate) {
            slide.setAttribute('aria-hidden', 'true');
            link.setAttribute('tabindex', '-1');
          } else if (slide === firstRealVisible) {
            slide.removeAttribute('aria-hidden');
            link.setAttribute('tabindex', '0');
          } else {
            slide.removeAttribute('aria-hidden');
            link.setAttribute('tabindex', '-1');
          }
        });
      }

      function updateViewAllLink(swiperInstance, data) {
        const viewAllBtn = document.getElementById("carousel-view-all");
        if (!viewAllBtn) return;

        try {
          const originalUrl = viewAllBtn.href.split("?")[0];
          const visibleSlides = swiperInstance.slides.filter(
            (slide, index) => {
              const slideProgress = swiperInstance.slides[index]
                ? swiperInstance.slides[index].progress
                : 1;
              return slideProgress >= -1 && slideProgress <= 1;
            },
          );

          const visibleIds = [];
          visibleSlides.forEach((slide) => {
            const nid = slide.getAttribute("data-nid");
            if (nid && !visibleIds.includes(nid)) {
              visibleIds.push(nid);
            }
          });

          if (visibleIds.length === 0) {
            data.forEach((item) => {
              if (item.nid && !visibleIds.includes(item.nid)) {
                visibleIds.push(item.nid);
              }
            });
          }

          const urlWithIds =
            visibleIds.length > 0
              ? `${originalUrl}?ids=${visibleIds.join(",")}`
              : originalUrl;

          viewAllBtn.href = urlWithIds;
        } catch (error) {
          console.warn("Errore aggiornamento View All:", error);
        }
      }
    },
  };
})(jQuery, Drupal);
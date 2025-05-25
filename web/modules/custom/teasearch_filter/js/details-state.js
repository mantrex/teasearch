/**
 * @file
 * Teasearch filter details state management.
 */

(function (Drupal, once) {
  "use strict";

  /**
   * Behavior for managing filter details state.
   */
  Drupal.behaviors.teasearchDetailsState = {
    attach: function (context, settings) {
      // Use 'once' to ensure we don't bind multiple times
      const detailsElements = once(
        "teasearch-details",
        ".teasearch-sidebar details[data-name], .teasearch-filter-group[data-name]",
        context
      );

      detailsElements.forEach(function (details) {
        const name = details.getAttribute("data-name");
        if (!name) return;

        const storageKey = "teasearch_open_" + name;

        // Restore state from sessionStorage
        const savedState = sessionStorage.getItem(storageKey);
        if (savedState === "1") {
          details.open = true;
        } else if (savedState === "0") {
          details.open = false;
        }
        // If no saved state, keep the default from HTML

        // Listen for toggle events
        details.addEventListener("toggle", function () {
          if (details.open) {
            sessionStorage.setItem(storageKey, "1");
          } else {
            sessionStorage.setItem(storageKey, "0");
          }
        });
      });
    },
  };

  /**
   * Behavior for handling filter form interactions.
   */
  Drupal.behaviors.teasearchFilterForm = {
    attach: function (context, settings) {
      const forms = once("teasearch-form", ".teasearch-filter-form", context);

      forms.forEach(function (form) {
        // Override form submission to handle multiple checkbox values correctly
        form.addEventListener("submit", function (e) {
          // Gestione checkbox multipli per taxonomy
          const checkboxGroups = {};
          const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');

          checkboxes.forEach(function (checkbox) {
            const name = checkbox.name;
            if (!checkboxGroups[name]) {
              checkboxGroups[name] = [];
            }
            checkboxGroups[name].push(checkbox.value);
          });

          // Rimuovi i checkbox originali dalla submission
          checkboxes.forEach(function (checkbox) {
            checkbox.disabled = true;
          });

          // Aggiungi hidden inputs con valori comma-separated
          Object.keys(checkboxGroups).forEach(function (name) {
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = name;
            hiddenInput.value = checkboxGroups[name].join(",");
            form.appendChild(hiddenInput);
          });
        });

        // Auto-submit on checkbox change (optional)
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function (checkbox) {
          checkbox.addEventListener("change", function () {
            // Debounce auto-submit to avoid too many requests
            if (form.submitTimeout) {
              clearTimeout(form.submitTimeout);
            }
            form.submitTimeout = setTimeout(function () {
              // Uncomment the next line if you want auto-submit on checkbox change
              // form.submit();
            }, 500);
          });
        });

        // Handle text input with debouncing
        const textInputs = form.querySelectorAll('input[type="text"]');
        textInputs.forEach(function (input) {
          input.addEventListener("input", function () {
            // Visual feedback for changes
            input.classList.add("changed");
            setTimeout(function () {
              input.classList.remove("changed");
            }, 2000);
          });

          // Submit on Enter key
          input.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
              e.preventDefault();
              form.submit();
            }
          });
        });
      });
    },
  };
})(Drupal, once);

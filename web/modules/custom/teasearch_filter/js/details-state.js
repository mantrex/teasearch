/**
 * @file
 * Teasearch filter details state management - Fix per checkbox submission.
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
        ".teasearch-sidebar details[data-name], .teasearch-filter-group[data-name], .filter-group[data-name]",
        context,
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
   * Behavior for handling filter form interactions - FIXED VERSION.
   */
  Drupal.behaviors.teasearchFilterForm = {
    attach: function (context, settings) {
      const forms = once("teasearch-form", ".teasearch-filter-form", context);

      forms.forEach(function (form) {
        // Enhanced form submission handler
        form.addEventListener("submit", function (e) {
          console.log("Form submission started");

          // Handle checkbox groups - convert array notation to comma-separated values
          const checkboxGroups = {};
          const checkboxes = form.querySelectorAll(
            'input[type="checkbox"]:checked',
          );

          checkboxes.forEach(function (checkbox) {
            let name = checkbox.name;

            // Remove array notation if present (e.g., "subjects[]" -> "subjects")
            if (name.endsWith("[]")) {
              name = name.slice(0, -2);
            }

            if (!checkboxGroups[name]) {
              checkboxGroups[name] = [];
            }
            checkboxGroups[name].push(checkbox.value);
          });

          console.log("Checkbox groups:", checkboxGroups);

          // Remove existing checkbox inputs to prevent double submission
          const allCheckboxes = form.querySelectorAll('input[type="checkbox"]');
          allCheckboxes.forEach(function (checkbox) {
            checkbox.disabled = true;
          });

          // Add hidden inputs with comma-separated values
          Object.keys(checkboxGroups).forEach(function (name) {
            // Remove any existing hidden input for this field
            const existingHidden = form.querySelector(
              'input[type="hidden"][name="' + name + '"]',
            );
            if (existingHidden) {
              existingHidden.remove();
            }

            // Create new hidden input
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = name;
            hiddenInput.value = checkboxGroups[name].join(",");
            form.appendChild(hiddenInput);

            console.log("Added hidden input:", name, "=", hiddenInput.value);
          });

          // Handle year range inputs from both century and date selectors
          handleYearRangeInputs(form);

          console.log("Form submission processed, continuing...");
        });

        // Auto-submit on checkbox change (optional, with debouncing)
        const checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function (checkbox) {
          checkbox.addEventListener("change", function () {
            // Clear existing timeout
            if (form.autoSubmitTimeout) {
              clearTimeout(form.autoSubmitTimeout);
            }

            // Set new timeout for auto-submit (disabled by default)
            form.autoSubmitTimeout = setTimeout(function () {
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

        // Handle number inputs (for year fields)
        const numberInputs = form.querySelectorAll('input[type="number"]');
        numberInputs.forEach(function (input) {
          input.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
              e.preventDefault();
              form.submit();
            }
          });
        });

        // Handle SELECT elements (for century selector)
        const selectInputs = form.querySelectorAll("select");
        selectInputs.forEach(function (select) {
          select.addEventListener("change", function () {
            // Optional auto-submit on select change
            // form.submit();
          });
        });
      });
    },
  };

  /**
   * Handle year range inputs from different sources.
   */
  function handleYearRangeInputs(form) {
    let yearFrom = null;
    let yearTo = null;

    // Priority 1: Check hidden inputs from century selector
    const hiddenYearFrom = form.querySelector("#hidden_year_from");
    const hiddenYearTo = form.querySelector("#hidden_year_to");

    if (hiddenYearFrom && hiddenYearFrom.value) {
      yearFrom = hiddenYearFrom.value;
    }
    if (hiddenYearTo && hiddenYearTo.value) {
      yearTo = hiddenYearTo.value;
    }

    // Priority 2: Check direct year inputs from date selector
    const directYearFrom = form.querySelector('input[name="year_from"]');
    const directYearTo = form.querySelector('input[name="year_to"]');

    if (directYearFrom && directYearFrom.value && !yearFrom) {
      yearFrom = directYearFrom.value;
    }
    if (directYearTo && directYearTo.value && !yearTo) {
      yearTo = directYearTo.value;
    }

    // Ensure we have proper year_from and year_to parameters
    if (yearFrom || yearTo) {
      // Remove any existing year inputs
      const existingYearInputs = form.querySelectorAll(
        'input[name="year_from"], input[name="year_to"]',
      );
      existingYearInputs.forEach(function (input) {
        if (
          input.type === "hidden" ||
          input.id === "hidden_year_from" ||
          input.id === "hidden_year_to"
        ) {
          return; // Keep hidden inputs from century selector
        }
        input.disabled = true;
      });

      // Add final hidden inputs for form submission
      if (yearFrom) {
        const finalFromInput = document.createElement("input");
        finalFromInput.type = "hidden";
        finalFromInput.name = "year_from";
        finalFromInput.value = yearFrom;
        form.appendChild(finalFromInput);
      }

      if (yearTo) {
        const finalToInput = document.createElement("input");
        finalToInput.type = "hidden";
        finalToInput.name = "year_to";
        finalToInput.value = yearTo;
        form.appendChild(finalToInput);
      }

      console.log("Year range set:", yearFrom, "to", yearTo);
    }
  }

  /**
   * Utility function to debug form data before submission.
   */
  function debugFormData(form) {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    for (let [key, value] of formData.entries()) {
      params.append(key, value);
    }

    console.log("Form data to submit:", params.toString());
    return params.toString();
  }
})(Drupal, once);

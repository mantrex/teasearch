/**
 * @file
 * Teasearch filter details state management - Fix per content_type con fallback.
 */

(function (Drupal, once) {
  "use strict";

  /**
   * Get content type from multiple sources.
   */
  function getContentType(context) {
    // Try 1: Get from wrapper data attribute
    const filtersWrapper = context.querySelector('.teasearch-filters[data-content-type]');
    if (filtersWrapper) {
      const contentType = filtersWrapper.getAttribute('data-content-type');
      if (contentType) {
        return contentType;
      }
    }

    // Try 2: Get from hidden input
    const hiddenInput = context.querySelector('input[name="content_type"]');
    if (hiddenInput && hiddenInput.value) {
      return hiddenInput.value;
    }

    // Try 3: Get from URL
    const urlParams = new URLSearchParams(window.location.search);
    const urlContentType = urlParams.get('content_type');
    if (urlContentType) {
      return urlContentType;
    }

    // Try 4: Get from URL path (e.g., /teasearch/texts)
    const pathMatch = window.location.pathname.match(/\/teasearch\/([^\/]+)/);
    if (pathMatch && pathMatch[1]) {
      return pathMatch[1];
    }

    console.warn('Content type not found, using generic key');
    return 'default';
  }

  /**
   * Behavior for managing filter details state.
   */
  Drupal.behaviors.teasearchDetailsState = {
    attach: function (context, settings) {
      const contentType = getContentType(context);

      // Use 'once' to ensure we don't bind multiple times
      const detailsElements = once(
        "teasearch-details",
        ".teasearch-sidebar details[data-name], .teasearch-filter-group[data-name], .filter-group[data-name]",
        context,
      );

      detailsElements.forEach(function (details, index) {
        const name = details.getAttribute("data-name");
        if (!name) return;

        // Include content_type in storage key
        const storageKey = "teasearch_open_" + contentType + "_" + name;

        // Restore state from sessionStorage
        const savedState = sessionStorage.getItem(storageKey);

        if (savedState === "1") {
          details.open = true;
        } else if (savedState === "0") {
          details.open = false;
        } else {
          // Default: first filter open, others closed
          details.open = (index === 0);
        }

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
        form.addEventListener("submit", function (e) {
          console.log("Form submission started");

          // Handle checkbox groups
          const checkboxGroups = {};
          const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');

          checkboxes.forEach(function (checkbox) {
            let name = checkbox.name;
            if (name.endsWith("[]")) {
              name = name.slice(0, -2);
            }
            if (!checkboxGroups[name]) {
              checkboxGroups[name] = [];
            }
            checkboxGroups[name].push(checkbox.value);
          });

          // Disable checkboxes
          const allCheckboxes = form.querySelectorAll('input[type="checkbox"]');
          allCheckboxes.forEach(function (checkbox) {
            checkbox.disabled = true;
          });

          // Add hidden inputs with comma-separated values
          Object.keys(checkboxGroups).forEach(function (name) {
            const hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.name = name;
            hiddenInput.value = checkboxGroups[name].join(",");
            form.appendChild(hiddenInput);
          });

          // Handle year range inputs
          handleYearRangeInputs(form);

          // Debug
          debugFormData(form);
        });
      });
    },
  };

  function handleYearRangeInputs(form) {
    let yearFrom = null;
    let yearTo = null;

    const hiddenYearFrom = form.querySelector("#hidden_year_from");
    const hiddenYearTo = form.querySelector("#hidden_year_to");

    if (hiddenYearFrom && hiddenYearFrom.value) {
      yearFrom = hiddenYearFrom.value;
    }
    if (hiddenYearTo && hiddenYearTo.value) {
      yearTo = hiddenYearTo.value;
    }

    const directYearFrom = form.querySelector('input[name="year_from"]');
    const directYearTo = form.querySelector('input[name="year_to"]');

    if (directYearFrom && directYearFrom.value && !yearFrom) {
      yearFrom = directYearFrom.value;
    }
    if (directYearTo && directYearTo.value && !yearTo) {
      yearTo = directYearTo.value;
    }

    if (yearFrom || yearTo) {
      const existingYearInputs = form.querySelectorAll('input[name="year_from"], input[name="year_to"]');
      existingYearInputs.forEach(function (input) {
        if (input.type === "hidden" || input.id === "hidden_year_from" || input.id === "hidden_year_to") {
          return;
        }
        input.disabled = true;
      });
      
      // Add final hidden inputs for form submission ONLY if not empty
      if (yearFrom && yearFrom.trim() !== '') {
        const finalFromInput = document.createElement("input");
        finalFromInput.type = "hidden";
        finalFromInput.name = "year_from";
        finalFromInput.value = yearFrom;
        form.appendChild(finalFromInput);
      }

      if (yearTo && yearTo.trim() !== '') {
        const finalToInput = document.createElement("input");
        finalToInput.type = "hidden";
        finalToInput.name = "year_to";
        finalToInput.value = yearTo;
        form.appendChild(finalToInput);
      }

      console.log("Year range set:", yearFrom, "to", yearTo);
    }
  }

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
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    console.log('Menu keyboard script loaded');

    const dropdownToggles = document.querySelectorAll('.navbar-nav .dropdown-toggle[role="button"]');
    console.log('Found dropdowns:', dropdownToggles.length);

    dropdownToggles.forEach(function (toggle) {
      console.log('Setting up dropdown:', toggle);

      toggle.addEventListener('keydown', function (e) {
        console.log('Key pressed:', e.key, 'on', toggle);

        if (e.key === 'Enter' || e.key === ' ') {
          console.log('Enter or Space detected, preventing default and toggling');
          e.preventDefault();
          e.stopPropagation();

          // Prova prima con click
          toggle.click();
          console.log('Click triggered');
        }
      });
    });
  });
})();
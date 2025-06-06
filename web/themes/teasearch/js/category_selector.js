(function() {

  document.addEventListener('DOMContentLoaded', function() {
    const categoryItems = document.querySelectorAll('.category-item');
    
    // Enhanced click handling
    categoryItems.forEach(function(item) {
      item.addEventListener('click', function(e) {
        // Se il click è sul link overlay, lascia che funzioni normalmente
        if (e.target.closest('.category-link-overlay')) {
          return;
        }
        
        // Altrimenti simula il click sul link
        const link = this.querySelector('.category-link-overlay');
        if (link) {
          link.click();
        }
      });
      
      // Keyboard navigation
      item.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const link = this.querySelector('.category-link-overlay');
          if (link) {
            link.click();
          }
        }
      });
    });
    
  });


}());
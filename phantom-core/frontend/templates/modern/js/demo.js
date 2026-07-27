// Modern Demo Pack — Clean Animations
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('modern-demo');

        // Clean hover — scale + shadow
        document.querySelectorAll('.product-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-6px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    });
})();

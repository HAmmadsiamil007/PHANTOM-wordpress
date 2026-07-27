// Vibrant Demo Pack — Energetic Animations
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('vibrant-demo');

        // Colorful pulse on brand logo
        var logo = document.querySelector('.brand-logo');
        if (logo) {
            logo.style.animation = 'pulse 2s ease-in-out infinite';
        }

        // Fun hover — bounce on product cards
        document.querySelectorAll('.product-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.01)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });
    });
})();

// Luxury Demo Pack — Premium Animations
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('luxury-demo');

        // Gold shimmer on brand logo
        var logo = document.querySelector('.brand-logo');
        if (logo) {
            setInterval(function() {
                logo.style.textShadow = '0 0 20px rgba(201,169,78,0.3)';
                setTimeout(function() {
                    logo.style.textShadow = '';
                }, 1000);
            }, 3000);
        }

        // Premium hover effect on product cards
        document.querySelectorAll('.product-card').forEach(function(card) {
            card.addEventListener('mouseenter', function() {
                var border = this.style.borderColor = '#C9A94E';
            });
            card.addEventListener('mouseleave', function() {
                this.style.borderColor = '';
            });
        });
    });
})();

(function(w) {
  'use strict';
  var R = w.PhantomRenderer = w.PhantomRenderer || {};
  var CR = R.ComponentRenderer;

  R.HeroRenderer = {
    render: function(data) {
      var image = data.image || '';
      var imageTablet = data.enable_responsive && data.image_tablet ? data.image_tablet : image;
      var imageMobile = data.enable_responsive && data.image_mobile ? data.image_mobile : image;

      var picture = '';
      if (data.enable_responsive) {
        picture = '<picture>';
        if (imageTablet !== image) {
          picture += '<source media="(max-width:' + (data.tablet_breakpoint || 1024) + 'px)" srcset="' + CR.sanitizeUrl(imageTablet) + '">';
        }
        if (imageMobile !== image) {
          picture += '<source media="(max-width:' + (data.mobile_breakpoint || 768) + 'px)" srcset="' + CR.sanitizeUrl(imageMobile) + '">';
        }
        picture += '<img src="' + CR.sanitizeUrl(image) + '" alt="' + CR.escapeHtml(data.title) + '" class="hero-image" loading="' + (data.loading || 'lazy') + '">';
        picture += '</picture>';
      }

      var html = '<section class="hero-section" style="--hero-overlay-opacity:' + (data.overlay_opacity || 0.3) + '">';
      if (picture) {
        html += picture;
      } else if (image) {
        html += '<img src="' + CR.sanitizeUrl(image) + '" alt="' + CR.escapeHtml(data.title) + '" class="hero-image" loading="' + (data.loading || 'lazy') + '">';
      }
      html += '<div class="hero-content">';
      html += '<h1 class="hero-title">' + CR.escapeHtml(data.title) + '</h1>';
      if (data.subtitle) html += '<p class="hero-subtitle">' + CR.escapeHtml(data.subtitle) + '</p>';
      if (data.description) html += '<p class="hero-description">' + CR.escapeHtml(data.description) + '</p>';
      html += '<a href="' + CR.sanitizeUrl(data.btn_url) + '" class="btn btn-primary hero-cta">' + CR.escapeHtml(data.btn_text) + '</a>';
      html += '</div></section>';

      return html;
    }
  };
})(window);

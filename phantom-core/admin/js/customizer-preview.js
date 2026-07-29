(function ($) {
  'use strict';

  var breakpoints = { tablet: 768, mobile: 544 };
  var responsiveStyleId = 'phantom-responsive-preview';

  var responsiveSheet = null;

  function getResponsiveSheet() {
    if (responsiveSheet) return responsiveSheet;
    var el = document.getElementById(responsiveStyleId);
    if (!el) {
      el = document.createElement('style');
      el.id = responsiveStyleId;
      document.head.appendChild(el);
    }
    responsiveSheet = el;
    return responsiveSheet;
  }

  function updateResponsiveCss(settingKey, cssVar, newval) {
    var sheet = getResponsiveSheet();
    var val = typeof newval === 'object' ? newval : { desktop: newval, tablet: '', mobile: '' };
    var rules = '';

    function addPx(v) { return /^\d+(\.\d+)?$/.test(v) ? v + 'px' : v; }
    if (val.desktop) rules += ':root { ' + cssVar + ': ' + addPx(val.desktop) + '; }';
    if (val.tablet) rules += '@media (max-width: ' + breakpoints.tablet + 'px) { :root { ' + cssVar + ': ' + addPx(val.tablet) + '; } }';
    if (val.mobile) rules += '@media (max-width: ' + breakpoints.mobile + 'px) { :root { ' + cssVar + ': ' + addPx(val.mobile) + '; } }';

    var escapedKey = settingKey.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    var regex = new RegExp('\\/\\* ' + escapedKey + ' \\*\\/[\\s\\S]*?\\/\\* \\/' + escapedKey + ' \\*\\/', 'g');
    var existing = sheet.textContent || '';
    if (regex.test(existing)) {
      existing = existing.replace(regex, '/* ' + settingKey + ' */' + rules + '/* /' + settingKey + ' */');
    } else {
      existing += '/* ' + settingKey + ' */' + rules + '/* /' + settingKey + ' */';
    }
    sheet.textContent = existing;
  }

  // Auto-bind CSS variables from PHP mapping
  if (typeof PhantomCustomizer !== 'undefined' && PhantomCustomizer.cssVarMap && Array.isArray(PhantomCustomizer.cssVarKeys)) {
    PhantomCustomizer.cssVarKeys.forEach(function (settingKey) {
      var settingId = 'phantom_' + settingKey;
      var cssVar = PhantomCustomizer.cssVarMap[settingKey];
      var needsPx = Array.isArray(PhantomCustomizer.cssVarPxKeys) && PhantomCustomizer.cssVarPxKeys.indexOf(settingKey) !== -1;
      var isResponsive = PhantomCustomizer.responsiveKeys && PhantomCustomizer.responsiveKeys.indexOf(settingKey) !== -1;
      var setting = wp.customize(settingId);
      if (setting) {
        setting.bind(function (newval) {
          if (isResponsive) {
            updateResponsiveCss(settingKey, cssVar, newval);
            return;
          }
          if (needsPx && /^\d+(\.\d+)?$/.test(newval)) newval += 'px';
          document.documentElement.style.setProperty(cssVar, newval);
        });
      }
    });
  }

  // Site title
  wp.customize('blogname', function (value) {
    value.bind(function (newval) {
      document.querySelectorAll('.brand-logo, [data-phantom="site_name"]').forEach(function (el) {
        el.textContent = newval;
      });
    });
  });

  // Hero Banner - Heading (accent text inside h1)
  wp.customize('phantom_home_banner_heading', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.hero-headline .hero-headline-accent');
      if (el) el.textContent = newval;
    });
  });

  // Hero Banner - Title (h1)
  wp.customize('phantom_home_banner_title', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('h1.hero-headline');
      if (el) {
        var accent = el.querySelector('.hero-headline-accent');
        el.textContent = '';
        var lines = (newval || '').split('\n');
        for (var i = 0; i < lines.length; i++) {
          if (i > 0) el.appendChild(document.createElement('br'));
          el.appendChild(document.createTextNode(lines[i]));
        }
        if (accent) el.appendChild(accent);
      }
    });
  });

  // Hero Banner - Description
  wp.customize('phantom_home_banner_description', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('p.hero-subline');
      if (el) {
        el.textContent = '';
        var lines = (newval || '').split('\n');
        for (var i = 0; i < lines.length; i++) {
          if (i > 0) el.appendChild(document.createElement('br'));
          el.appendChild(document.createTextNode(lines[i]));
        }
      }
    });
  });

  // Hero Banner - Button Text (primary CTA)
  wp.customize('phantom_home_banner_btn_text', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.hero-cta-group .btn-primary');
      if (el) {
        var icon = el.querySelector('i');
        el.textContent = '';
        el.appendChild(document.createTextNode(' ' + newval + ' '));
        if (icon) el.appendChild(icon);
      }
    });
  });

  // Hero Banner - Button URL
  wp.customize('phantom_home_banner_btn_url', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.hero-cta-group .btn-primary');
      if (el) el.href = newval;
    });
  });

  // Hero Banner - Image 1
  wp.customize('phantom_home_banner_img1', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.swiper-slide.hero-slide:first-child .hero-slide-bg img');
      if (el) el.src = newval;
    });
  });

  // Hero Banner - Image 2
  wp.customize('phantom_home_banner_img2', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.swiper-slide.hero-slide:last-child .hero-slide-bg img');
      if (el) el.src = newval;
    });
  });

  // Logos — revert to default logo image when custom logo is removed
  var defaultLogo = PhantomCustomizer.defaultImages ? PhantomCustomizer.defaultImages.logo : '';
  wp.customize('phantom_general_site_logo', function (value) {
    value.bind(function (newval) {
      var effectiveVal = newval || defaultLogo;
      var el = document.querySelector('.brand-logo img, img[data-phantom="site_logo"]');
      if (el) {
        el.src = effectiveVal;
      } else {
        var brand = document.querySelector('.brand-logo');
        if (brand && brand.tagName === 'A') {
          // Replace text-only brand logo with image
          brand.innerHTML = '<img src="' + effectiveVal + '" alt="' + (document.title || 'Logo') + '" height="40">';
        }
      }
    });
  });
  wp.customize('phantom_footer_logo', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.footer-logo img');
      if (el) {
        el.src = newval;
      } else {
        var brand = document.querySelector('a.footer-logo');
        if (brand && !brand.querySelector('img')) {
          brand.style.backgroundImage = 'url(' + newval + ')';
          brand.style.backgroundSize = 'contain';
          brand.style.backgroundRepeat = 'no-repeat';
          brand.style.backgroundPosition = 'center';
          brand.textContent = '';
          brand.style.display = 'inline-block';
        }
      }
    });
  });

  // Favicon — revert to default when custom favicon is removed
  var defaultFavicon = PhantomCustomizer.defaultImages ? PhantomCustomizer.defaultImages.favicon : '';
  wp.customize('phantom_branding_favicon', function (value) {
    value.bind(function (newval) {
      var effectiveVal = newval || defaultFavicon;
      var link = document.querySelector('link[rel="icon"]');
      if (link) link.href = effectiveVal;
    });
  });

  // Footer - About Text
  wp.customize('phantom_footer_about_text', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('p.footer-tagline');
      if (el) {
        el.textContent = '';
        var lines = (newval || '').split('\n');
        for (var i = 0; i < lines.length; i++) {
          if (i > 0) el.appendChild(document.createElement('br'));
          el.appendChild(document.createTextNode(lines[i]));
        }
      }
    });
  });

  // Footer - Address
  wp.customize('phantom_footer_address', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.footer-newsletter p, [data-phantom="footer_address"]');
      if (el) {
        el.textContent = '';
        var lines = (newval || '').split('\n');
        for (var i = 0; i < lines.length; i++) {
          if (i > 0) el.appendChild(document.createElement('br'));
          el.appendChild(document.createTextNode(lines[i]));
        }
      }
    });
  });

  // Footer - Copyright
  wp.customize('phantom_footer_copyright', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.footer-legal span:first-child');
      if (el) {
        el.textContent = '';
        var text = (newval || '').replace('%d', new Date().getFullYear());
        var lines = text.split('\n');
        for (var i = 0; i < lines.length; i++) {
          if (i > 0) el.appendChild(document.createElement('br'));
          el.appendChild(document.createTextNode(lines[i]));
        }
      }
    });
  });

  // ─── HERO RESPONSIVE MEDIA ───────────────────────────────

  // Desktop Hero Image — update img src + bg image (revert to default when empty)
  var defaultHeroDesktop = PhantomCustomizer.defaultImages ? PhantomCustomizer.defaultImages.heroDesktop : '';
  wp.customize('phantom_hero_banner_image', function (value) {
    value.bind(function (newval) {
      var img = document.querySelector('[data-hero-area] img.hero-image, [data-phantom-hero-img]');
      var effectiveVal = newval || defaultHeroDesktop;
      if (img) img.src = effectiveVal;
      var bgEls = document.querySelectorAll('[data-phantom-bg="hero"]');
      bgEls.forEach(function (el) {
        el.style.backgroundImage = effectiveVal ? 'url("' + effectiveVal.replace(/[^a-zA-Z0-9\-._~:\/?#@!$&'(*+,;=%]/g, '') + '")' : '';
      });
    });
  });

  // Tablet Hero Image — revert to default when empty
  var defaultHeroTablet = PhantomCustomizer.defaultImages ? PhantomCustomizer.defaultImages.heroTablet : '';
  wp.customize('phantom_hero_image_tablet', function (value) {
    value.bind(function (newval) {
      var effectiveVal = newval || defaultHeroTablet;
      var source = document.querySelector('[data-hero-area] picture source[data-device="tablet"]');
      if (source) {
        source.srcset = effectiveVal;
        source.removeAttribute('disabled');
      }
    });
  });

  // Mobile Hero Image — revert to default when empty
  var defaultHeroMobile = PhantomCustomizer.defaultImages ? PhantomCustomizer.defaultImages.heroMobile : '';
  wp.customize('phantom_hero_image_mobile', function (value) {
    value.bind(function (newval) {
      var effectiveVal = newval || defaultHeroMobile;
      var source = document.querySelector('[data-hero-area] picture source[data-device="mobile"]');
      if (source) {
        source.srcset = effectiveVal;
        source.removeAttribute('disabled');
      }
    });
  });

  // Image Loading
  wp.customize('phantom_hero_loading', function (value) {
    value.bind(function (newval) {
      document.querySelectorAll('[data-hero-area] img.hero-image').forEach(function (el) {
        el.loading = newval || 'auto';
      });
    });
  });

  // Hero Fit — CSS var
  wp.customize('phantom_hero_fit', function (value) {
    value.bind(function (newval) {
      document.documentElement.style.setProperty('--hero-object-fit', newval || 'cover');
    });
  });

  // Hero Position — CSS var + background position
  wp.customize('phantom_hero_position', function (value) {
    value.bind(function (newval) {
      var pos = newval || 'center';
      document.documentElement.style.setProperty('--hero-object-position', pos);
      var bgPos = '50%';
      if (pos === 'top') bgPos = '50% 0%';
      else if (pos === 'bottom') bgPos = '50% 100%';
      else if (pos === 'left') bgPos = '0% 50%';
      else if (pos === 'right') bgPos = '100% 50%';
      document.documentElement.style.setProperty('--hero-bg-position', bgPos);
    });
  });

  // Overlay Opacity — CSS var
  wp.customize('phantom_hero_overlay_opacity', function (value) {
    value.bind(function (newval) {
      document.documentElement.style.setProperty('--hero-overlay-opacity', (parseInt(newval, 10) || 0) + '%');
    });
  });

  // Header Style — toggle class on body
  wp.customize('phantom_header_style', function (value) {
    value.bind(function (newval) {
      var body = document.body;
      ['header-default', 'header-centered', 'header-minimal'].forEach(function (c) {
        body.classList.remove(c);
      });
      if (newval) body.classList.add('header-' + newval);
    });
  });

  // Blog Layout — toggle class
  wp.customize('phantom_blog_layout', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('#main-content .blog-posts, .blog-grid, [data-phantom="blog"]');
      if (!el) el = document.querySelector('main .row, main .container');
      if (el) {
        ['blog-grid', 'blog-list', 'blog-masonry'].forEach(function (c) {
          el.classList.remove(c);
        });
        if (newval && newval !== 'default') el.classList.add('blog-' + newval);
      }
    });
  });

  // Footer Columns — toggle layout class
  wp.customize('phantom_footer_columns', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('footer .row, footer .footer-main .row');
      if (el) {
        var cols = parseInt(newval, 10) || 4;
        el.className = el.className.replace(/\brow-cols-\d+/g, '');
        el.classList.add('row-cols-' + cols);
      }
    });
  });

  // Selective Refresh - Partials
  if (typeof PhantomPartials !== 'undefined') {
    Object.keys(PhantomPartials).forEach(function (key) {
      var partial = PhantomPartials[key];
      var settingId = 'phantom_' + key;
      var selector = partial.selector || '';
      if (!selector) return;

      wp.customize(settingId, function (value) {
        value.bind(function () {
          var url = wp.customize.settings.url ? wp.customize.settings.url.ajax : '';
          var restUrl = (window.PhantomCustomizer && PhantomCustomizer.restUrl) ? PhantomCustomizer.restUrl : (wp.customize.settings.url || {}).rest_base || '';

          var endpoint = (restUrl ? restUrl.replace(/\/$/, '') : wpApiSettings && wpApiSettings.root ? wpApiSettings.root.replace(/\/$/, '') : '/wp-json') + '/phantom/v1/partial?partial=' + encodeURIComponent(key);

          fetch(endpoint, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': wpApiSettings && wpApiSettings.nonce ? wpApiSettings.nonce : '' }
          })
          .then(function (r) {
            if (!r.ok) throw new Error('Partial fetch failed: ' + r.status);
            return r.json();
          })
          .then(function (data) {
            if (data.html !== undefined) {
              var target = document.querySelector(selector);
              if (target) {
                target.innerHTML = data.html;
              }
            }
          })
          .catch(function (err) {
            console.warn('[Phantom Partial]', err.message);
          });
        });
      });
    });
  }

})(jQuery);

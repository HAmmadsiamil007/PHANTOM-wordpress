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
      wp.customize(settingId, function (value) {
        value.bind(function (newval) {
          if (isResponsive) {
            updateResponsiveCss(settingKey, cssVar, newval);
            return;
          }
          if (needsPx && /^\d+(\.\d+)?$/.test(newval)) newval += 'px';
          document.documentElement.style.setProperty(cssVar, newval);
        });
      });
    });
  }

  // Header sticky — class toggle
  wp.customize('phantom_header_sticky', function (value) {
    value.bind(function (newval) {
      var h = document.querySelector('header');
      if (h) h.classList.toggle('sticky-header', !!newval);
    });
  });

  // Site title
  wp.customize('blogname', function (value) {
    value.bind(function (newval) {
      document.querySelectorAll('.site-title, [data-phantom="site_name"]').forEach(function (el) {
        el.textContent = newval;
      });
    });
  });

  // Hero Banner - Heading
  wp.customize('phantom_home_banner_heading', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-span');
      if (el) el.textContent = newval;
    });
  });

  // Hero Banner - Title (h1)
  wp.customize('phantom_home_banner_title', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-con h1');
      if (el) {
        el.innerHTML = '';
        el.appendChild(document.createTextNode(newval));
      }
    });
  });

  // Hero Banner - Description
  wp.customize('phantom_home_banner_description', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-con .center-context p');
      if (el) {
        el.innerHTML = '';
        el.appendChild(document.createTextNode(newval));
      }
    });
  });

  // Hero Banner - Button Text
  wp.customize('phantom_home_banner_btn_text', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-con .secondary_btn');
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
      var el = document.querySelector('.banner-con .secondary_btn');
      if (el) el.href = newval;
    });
  });

  // Hero Banner - Image 1
  wp.customize('phantom_home_banner_img1', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-img1');
      if (el) el.src = newval;
    });
  });

  // Hero Banner - Image 2
  wp.customize('phantom_home_banner_img2', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-img2');
      if (el) el.src = newval;
    });
  });

  // Logos
  wp.customize('phantom_general_site_logo', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.navbar-brand img.logo, .header-logo img, img[data-phantom="site_logo"], figure.logo img');
      if (el) el.src = newval;
    });
  });
  wp.customize('phantom_footer_logo', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.footer-logo img');
      if (el) el.src = newval;
    });
  });

  // Hero Banner Image
  wp.customize('phantom_hero_banner_image', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-img1');
      if (el) el.src = newval;
    });
  });

  // Favicon
  wp.customize('phantom_branding_favicon', function (value) {
    value.bind(function (newval) {
      var link = document.querySelector('link[rel="icon"]');
      if (link) link.href = newval;
    });
  });

  // Footer - About Text
  wp.customize('phantom_footer_about_text', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.logo-content .text.text-size-14');
      if (el) el.innerHTML = newval.replace(/\n/g, '<br>');
    });
  });

  // Footer - Address
  wp.customize('phantom_footer_address', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.icon ul.list-unstyled a.address, .icon ul.list-unstyled li:last-child a');
      if (el) el.innerHTML = newval.replace(/\n/g, '<br>');
    });
  });

  // Footer - Copyright
  wp.customize('phantom_footer_copyright', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.copyright .content p');
      if (el) el.innerHTML = newval.replace('%d', new Date().getFullYear()).replace(/\n/g, '<br>');
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
              } else {
                console.warn('[Phantom Partial] selector not found:', selector);
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

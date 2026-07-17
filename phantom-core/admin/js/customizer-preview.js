(function ($) {
  'use strict';

  // Auto-bind CSS variables from PHP mapping
  if (typeof PhantomCustomizer !== 'undefined' && PhantomCustomizer.cssVarMap) {
    PhantomCustomizer.cssVarKeys.forEach(function (settingKey) {
      var settingId = 'phantom_' + settingKey;
      var cssVar = PhantomCustomizer.cssVarMap[settingKey];
      var needsPx = PhantomCustomizer.cssVarPxKeys.indexOf(settingKey) !== -1;
      wp.customize(settingId, function (value) {
        value.bind(function (newval) {
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
      if (el) el.innerHTML = newval.replace(/\n/g, '<br>');
    });
  });

  // Hero Banner - Description
  wp.customize('phantom_home_banner_description', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.banner-con .center-context p');
      if (el) el.innerHTML = newval.replace(/\n/g, '<br>');
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
      var el = document.querySelector('.navbar-brand img.logo, .header-logo img, img[data-phantom="site_logo"]');
      if (el) el.src = newval;
    });
  });
  wp.customize('phantom_footer_logo', function (value) {
    value.bind(function (newval) {
      var el = document.querySelector('.footer-logo img');
      if (el) el.src = newval;
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

})(jQuery);

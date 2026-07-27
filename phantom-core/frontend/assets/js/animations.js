// AETHER — Premium Animation System v4.0
// GSAP + ScrollTrigger powered cinematic motion
// Every card, button, text, and content slides from left or right on scroll

(function () {
  'use strict';

  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reducedMotion) {
    document.documentElement.classList.add('no-motion');
    return;
  }

  document.documentElement.classList.add('has-motion');

  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
    console.warn('AETHER Motion: GSAP or ScrollTrigger not loaded');
    return;
  }

  gsap.registerPlugin(ScrollTrigger);
  gsap.defaults({ ease: 'power3.out', duration: 0.85 });

  // ═══════════════════════════════════════════════════════════════
  // PREMIUM REVEAL PRESETS — Left/Right directional entrances
  // ═══════════════════════════════════════════════════════════════

  var revealPresets = {
    // ─── Slide from Left ───
    // ─── Slide from Left (no blur — blur reserved for blur-in preset) ───
    'slide-left':        { from: { x: -80, autoAlpha: 0 }, to: { x: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'slide-left-soft':   { from: { x: -50, autoAlpha: 0 }, to: { x: 0, autoAlpha: 1 }, ease: 'power3.out' },
    'slide-left-far':    { from: { x: -120, autoAlpha: 0 }, to: { x: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'slide-left-up':     { from: { x: -60, y: 30, autoAlpha: 0 }, to: { x: 0, y: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'slide-left-rotate': { from: { x: -70, rotation: -3, autoAlpha: 0 }, to: { x: 0, rotation: 0, autoAlpha: 1 }, ease: 'power4.out' },

    // ─── Slide from Right ───
    'slide-right':        { from: { x: 80, autoAlpha: 0 }, to: { x: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'slide-right-soft':   { from: { x: 50, autoAlpha: 0 }, to: { x: 0, autoAlpha: 1 }, ease: 'power3.out' },
    'slide-right-far':    { from: { x: 120, autoAlpha: 0 }, to: { x: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'slide-right-up':     { from: { x: 60, y: 30, autoAlpha: 0 }, to: { x: 0, y: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'slide-right-rotate': { from: { x: 70, rotation: 3, autoAlpha: 0 }, to: { x: 0, rotation: 0, autoAlpha: 1 }, ease: 'power4.out' },

    // ─── Fade variants ───
    'fade-up':     { from: { y: 50, autoAlpha: 0 }, to: { y: 0, autoAlpha: 1 }, ease: 'power4.out' },
    'fade-down':   { from: { y: -50, autoAlpha: 0 }, to: { y: 0, autoAlpha: 1 }, ease: 'power4.out' },

    // ─── Scale variants ───
    'scale':       { from: { scale: 0.88, autoAlpha: 0 }, to: { scale: 1, autoAlpha: 1 }, ease: 'back.out(1.2)' },
    'scale-up':    { from: { scale: 0.85, y: 30, autoAlpha: 0 }, to: { scale: 1, y: 0, autoAlpha: 1 }, ease: 'back.out(1.2)' },

    // ─── Blur entrance ───
    'blur-in':     { from: { y: 20, autoAlpha: 0, filter: 'blur(16px)' }, to: { y: 0, autoAlpha: 1, filter: 'blur(0px)' }, ease: 'power4.out' },

    // ─── Clip reveal ───
    'clip-reveal': { from: { clipPath: 'inset(0 0 100% 0)', autoAlpha: 0 }, to: { clipPath: 'inset(0 0 0% 0)', autoAlpha: 1 }, ease: 'expo.out' }
  };

  // ═══════════════════════════════════════════════════════════════
  // 1. TEXT REVEAL — Word-by-word staggered entrance
  // ═══════════════════════════════════════════════════════════════

  function splitWords(element) {
    if (element.dataset.motionSplit === 'true') return;
    var text = element.textContent || '';
    var parts = text.split(/(\s+)/);
    element.textContent = '';
    element.setAttribute('aria-label', text.trim());
    var index = 0;
    parts.forEach(function (part) {
      if (!part.trim()) {
        element.appendChild(document.createTextNode(part));
        return;
      }
      var mask = document.createElement('span');
      var word = document.createElement('span');
      mask.className = 'motion-word-mask';
      mask.setAttribute('aria-hidden', 'true');
      word.className = 'motion-word';
      word.textContent = part;
      word.style.setProperty('--word-index', index);
      mask.appendChild(word);
      element.appendChild(mask);
      index += 1;
    });
    element.dataset.motionSplit = 'true';
  }

  function initTextReveals() {
    // Word-by-word staggered entrance
    gsap.utils.toArray('[data-motion-text="words"]').forEach(function (element) {
      splitWords(element);
      var words = element.querySelectorAll('.motion-word');
      gsap.set(element, { autoAlpha: 1 });
      gsap.fromTo(words,
        { yPercent: 110, autoAlpha: 0, filter: 'blur(8px)' },
        {
          yPercent: 0, autoAlpha: 1, filter: 'blur(0px)',
          duration: 0.9, ease: 'power4.out', stagger: 0.055,
          scrollTrigger: { trigger: element, start: 'top 82%', once: true }
        }
      );
    });

    // Line-by-line staggered entrance
    gsap.utils.toArray('[data-motion-text="lines"]').forEach(function (element) {
      var lines = element.querySelectorAll('.motion-line');
      var targets = lines.length ? lines : element.children;
      if (!targets.length) return;
      gsap.set(element, { autoAlpha: 1 });
      gsap.fromTo(targets,
        { yPercent: 100, autoAlpha: 0, filter: 'blur(8px)' },
        {
          yPercent: 0, autoAlpha: 1, filter: 'blur(0px)',
          duration: 1, ease: 'power4.out', stagger: 0.11,
          scrollTrigger: { trigger: element, start: 'top 84%', once: true }
        }
      );
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 2. SCROLL REVEALS — Premium left/right directional entrances
  // ═══════════════════════════════════════════════════════════════

  function initScrollReveals() {
    autoAssignReveals();

    // ─── Group stagger animations (cards in grids) ───
    gsap.utils.toArray('[data-reveal-group]').forEach(function (group) {
      var items = group.querySelectorAll('[data-reveal-item]');
      if (!items.length) return;
      gsap.set(group, { autoAlpha: 1 });
      gsap.fromTo(items,
        { y: 36, autoAlpha: 0, filter: 'blur(6px)' },
        {
          y: 0, autoAlpha: 1, filter: 'blur(0px)',
          duration: 0.95, ease: 'power4.out', stagger: 0.08,
          scrollTrigger: { trigger: group, start: 'top 82%', once: true }
        }
      );
    });

    // ─── Individual element reveals ───
    // NOTE: No gsap.set() before fromTo — the fromTo handles initial state.
    // CSS visibility:hidden keeps elements hidden until GSAP applies the from state.
    gsap.utils.toArray('[data-reveal]:not([data-reveal-item])').forEach(function (element) {
      var presetKey = element.dataset.reveal || 'fade-up';
      var preset = revealPresets[presetKey] || revealPresets['fade-up'];

      var toVars = Object.assign({}, preset.to, {
        duration: 0.95,
        ease: preset.ease || 'expo.out',
        delay: Number(element.dataset.revealDelay || 0),
        scrollTrigger: { trigger: element, start: 'top 86%', once: true }
      });

      gsap.fromTo(element, preset.from, toVars);
    });

    // ─── Legacy reveal classes (backward compatible) ───
    initLegacyReveals();
  }

  // ═══════════════════════════════════════════════════════════════
  // AUTO-ASSIGN REVEALS — Every element gets a left/right direction
  // ═══════════════════════════════════════════════════════════════

  function autoAssignReveals() {

    // ─── Section Headers (always slide up) ───
    gsap.utils.toArray('.section-header').forEach(function (el) {
      if (!el.dataset.reveal && !el.dataset.motionText) el.dataset.reveal = 'fade-up';
    });

    gsap.utils.toArray('.section-label').forEach(function (el) {
      if (!el.dataset.reveal && !el.dataset.motionText) el.dataset.reveal = 'slide-left-soft';
    });

    gsap.utils.toArray('.section-title').forEach(function (el) {
      if (!el.dataset.reveal && !el.dataset.motionText) el.dataset.reveal = 'fade-up';
    });

    gsap.utils.toArray('.section-subtitle').forEach(function (el) {
      if (!el.dataset.reveal && !el.dataset.motionText) el.dataset.reveal = 'slide-right-soft';
    });

    // ─── Category Cards — Alternating left/right ───
    gsap.utils.toArray('.category-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-rotate' : 'slide-right-rotate';
        el.dataset.revealDelay = (i * 0.15).toFixed(2);
      }
    });

    // ─── Product Cards — Alternating left/right by row ───
    gsap.utils.toArray('.product-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        var colInRow = i % 4;
        var directions = ['slide-left', 'slide-right', 'slide-left-soft', 'slide-right-soft'];
        el.dataset.reveal = directions[colInRow];
        el.dataset.revealDelay = (colInRow * 0.1).toFixed(2);
      }
    });

    // ─── Blog Cards — Alternating left/right ───
    gsap.utils.toArray('.blog-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left' : 'slide-right';
        el.dataset.revealDelay = (i * 0.12).toFixed(2);
      }
    });

    // ─── Review Cards — Alternating from sides ───
    gsap.utils.toArray('.review-card, .testimonial-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-soft' : 'slide-right-soft';
        el.dataset.revealDelay = (i * 0.1).toFixed(2);
      }
    });

    // ─── Team Cards — Alternating with scale ───
    gsap.utils.toArray('.team-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-rotate' : 'slide-right-rotate';
        el.dataset.revealDelay = (i * 0.12).toFixed(2);
      }
    });

    // ─── FAQ Items — Staggered from right ───
    gsap.utils.toArray('.faq-item').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-soft' : 'slide-right-soft';
        el.dataset.revealDelay = (i * 0.08).toFixed(2);
      }
    });

    // ─── About Page: Mission (alternating) ───
    gsap.utils.toArray('.mission-content').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left';
    });
    gsap.utils.toArray('.mission-image').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-right';
    });

    // ─── Story Quote ───
    gsap.utils.toArray('.story-quote').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'blur-in';
    });

    // ─── Page Hero Content ───
    gsap.utils.toArray('.page-hero-content').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'fade-up';
    });

    // ─── Feature Rows / About Features / Contact Info — Alternating ───
    gsap.utils.toArray('.feature-row').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left' : 'slide-right';
        el.dataset.revealDelay = (i * 0.1).toFixed(2);
      }
    });

    gsap.utils.toArray('.about-feature').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-rotate' : 'slide-right-rotate';
        el.dataset.revealDelay = (i * 0.12).toFixed(2);
      }
    });

    gsap.utils.toArray('.contact-info-item').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left' : 'slide-right';
        el.dataset.revealDelay = (i * 0.1).toFixed(2);
      }
    });

    // ─── Buttons / CTAs — Slide from left ───
    gsap.utils.toArray('.btn-primary, .btn-outline, .btn-lg, .section-cta').forEach(function (el) {
      if (!el.dataset.reveal && !el.closest('[data-reveal]')) {
        el.dataset.reveal = 'slide-left-soft';
      }
    });

    // ─── Footer ───
    var footer = document.querySelector('.footer');
    if (footer && !footer.dataset.reveal) footer.dataset.reveal = 'fade-up';

    // ─── Footer link columns — Staggered from right ───
    gsap.utils.toArray('.footer-links, .footer-brand, .footer-newsletter').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-soft' : 'slide-right-soft';
        el.dataset.revealDelay = (i * 0.08).toFixed(2);
      }
    });

    // ─── FAQ CTA ───
    gsap.utils.toArray('.faq-cta').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left-soft';
    });

    // ─── Stat Items — Alternating from sides ───
    gsap.utils.toArray('.stat-item, .stat-number').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left' : 'slide-right';
        el.dataset.revealDelay = (i * 0.1).toFixed(2);
      }
    });

    // ─── Newsletter ───
    gsap.utils.toArray('.newsletter-inner').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left-soft';
    });

    gsap.utils.toArray('.newsletter-form').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-right-soft';
    });

    // ─── Confirmation Section ───
    gsap.utils.toArray('.confirmation-section').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'scale';
    });

    // ─── Content Pages ───
    gsap.utils.toArray('.content-page').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'fade-up';
    });

    // ─── Product Detail Page ───
    gsap.utils.toArray('.pd-info, .pd-info-inner').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-right';
    });
    gsap.utils.toArray('.pd-gallery-main').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'scale';
    });
    gsap.utils.toArray('.pd-accordion-item').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left-soft' : 'slide-right-soft';
        el.dataset.revealDelay = (i * 0.08).toFixed(2);
      }
    });
    gsap.utils.toArray('.pd-review-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-left' : 'slide-right';
        el.dataset.revealDelay = (i * 0.1).toFixed(2);
      }
    });
    gsap.utils.toArray('.pd-specs, .pd-related').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'fade-up';
    });

    // ─── Contact Page ───
    gsap.utils.toArray('.contact-form, .contact-form-wrap').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left';
    });
    gsap.utils.toArray('.info-card').forEach(function (el, i) {
      if (!el.dataset.reveal) {
        el.dataset.reveal = i % 2 === 0 ? 'slide-right' : 'slide-left';
        el.dataset.revealDelay = (i * 0.12).toFixed(2);
      }
    });

    // ─── Filter Bar ───
    gsap.utils.toArray('.filter-bar').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left-soft';
    });

    // ─── Shop Grid ───
    gsap.utils.toArray('.shop-grid-section').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'fade-up';
    });

    // NOTE: Nested elements inside .product-card (product-image, product-info,
    // product-name, product-price, product-badge) are NOT assigned data-reveal.
    // The parent .product-card reveal handles the entire card entrance.
    // Individual children animate via CSS transition on hover.

    // ─── Reviews Summary ───
    gsap.utils.toArray('.reviews-summary, .reviews-score').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-right-soft';
    });

    // ─── Score Number ───
    gsap.utils.toArray('.score-number').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'scale';
    });

    // ─── Score Stars ───
    gsap.utils.toArray('.score-stars').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left-soft';
    });

    // ─── Social Links in Footer ───
    gsap.utils.toArray('.footer-social').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-right-soft';
    });

    // ─── Payment Icons ───
    gsap.utils.toArray('.footer-payments').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'slide-left-soft';
    });

    // ─── Footer Bottom ───
    gsap.utils.toArray('.footer-bottom').forEach(function (el) {
      if (!el.dataset.reveal) el.dataset.reveal = 'fade-up';
    });

    // NOTE: Nested elements inside .review-card (review-stars, review-title,
    // review-text, review-header) are NOT assigned data-reveal.
    // The parent .review-card reveal handles the entire card entrance.
  }

  // ═══════════════════════════════════════════════════════════════
  // 3. IMAGE CLIP REVEALS
  // ═══════════════════════════════════════════════════════════════

  function initImageReveals() {
    gsap.utils.toArray('[data-image-reveal]').forEach(function (figure) {
      var image = figure.querySelector('img');
      if (!image) return;
      gsap.set(figure, { autoAlpha: 1 });
      var tl = gsap.timeline({
        scrollTrigger: { trigger: figure, start: 'top 82%', once: true }
      });
      tl.fromTo(figure,
        { clipPath: 'inset(0 0 100% 0)' },
        { clipPath: 'inset(0 0 0% 0)', duration: 1.1, ease: 'expo.out' }
      ).fromTo(image,
        { scale: 1.08, autoAlpha: 0.75 },
        { scale: 1, autoAlpha: 1, duration: 1.2, ease: 'expo.out' },
        0
      );
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 4. SECTION SNAPPING — Hero parallax + page snap
  // ═══════════════════════════════════════════════════════════════

  function initSectionSnapping() {
    var heroSlider = document.querySelector('.hero-slider');
    if (heroSlider) {
      // Hero content fades out on scroll
      var heroContent = heroSlider.querySelector('.hero-slide-content');
      if (heroContent) {
        gsap.to(heroContent, {
          y: -60, opacity: 0, ease: 'none',
          scrollTrigger: {
            trigger: heroSlider, start: 'top top', end: '60% top',
            scrub: 1
          }
        });
      }

      // Scroll indicator fades out
      var scrollIndicator = heroSlider.querySelector('.scroll-indicator');
      if (scrollIndicator) {
        gsap.to(scrollIndicator, {
          opacity: 0, y: -20, ease: 'none',
          scrollTrigger: {
            trigger: heroSlider, start: '10% top', end: '30% top',
            scrub: 1
          }
        });
      }

      // Hero background parallax
      gsap.utils.toArray('.hero-slide-bg img').forEach(function (img) {
        gsap.to(img, {
          y: 80, ease: 'none',
          scrollTrigger: {
            trigger: heroSlider, start: 'top top', end: 'bottom top',
            scrub: 1.5, invalidateOnRefresh: true
          }
        });
      });
    }

    // Story section background parallax
    gsap.utils.toArray('.story-section').forEach(function (section) {
      gsap.to(section, {
        backgroundPosition: '50% 60%', ease: 'none',
        scrollTrigger: {
          trigger: section, start: 'top bottom', end: 'bottom top',
          scrub: 1.5
        }
      });
    });

    // Page-level snap (Instagram Reels feel)
    initPageSnap();
  }

  // ─── Page-Level Snap ───
  function initPageSnap() {
    if (!document.body.classList.contains('home-page')) return;

    var snapSections = gsap.utils.toArray('.categories, .bestsellers, .reviews, .faq-section, .newsletter-section');
    if (snapSections.length < 2) return;

    function computeSnapPositions() {
      var scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
      if (scrollHeight <= 0) return [];
      return snapSections.map(function (s) {
        var top = s.getBoundingClientRect().top + window.scrollY;
        return Math.max(0, Math.min(1, top / scrollHeight));
      });
    }

    var snapPositions = computeSnapPositions();
    if (snapPositions.length === 0) return;

    ScrollTrigger.create({
      trigger: document.body,
      start: 'top top',
      end: 'bottom bottom',
      snap: {
        snapTo: function () { return snapPositions; },
        duration: { min: 0.3, max: 0.5 },
        delay: 0.08,
        ease: 'power1.inOut'
      },
      onRefresh: function () {
        snapPositions = computeSnapPositions();
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 5. MAGNETIC HOVER — Buttons follow cursor
  // ═══════════════════════════════════════════════════════════════

  function initMagnetic() {
    if (window.matchMedia('(pointer: coarse)').matches) return;

    gsap.utils.toArray('.btn-primary, .hero-nav-btn').forEach(function (el) {
      if (!el.dataset.magnetic) el.dataset.magnetic = '0.15';
    });

    gsap.utils.toArray('[data-magnetic]').forEach(function (element) {
      var strength = Number(element.dataset.magnetic || 0.2);
      var xTo = gsap.quickTo(element, 'x', { duration: 0.45, ease: 'power3.out' });
      var yTo = gsap.quickTo(element, 'y', { duration: 0.45, ease: 'power3.out' });

      element.addEventListener('pointermove', function (event) {
        var rect = element.getBoundingClientRect();
        var x = (event.clientX - rect.left - rect.width / 2) * strength;
        var y = (event.clientY - rect.top - rect.height / 2) * strength;
        xTo(x);
        yTo(y);
      });

      element.addEventListener('pointerleave', function () {
        xTo(0);
        yTo(0);
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 6. IMAGE PARALLAX — Subtle zoom on scroll
  // ═══════════════════════════════════════════════════════════════

  function initImageParallax() {
    gsap.utils.toArray('.category-card-bg img, .product-image img, .pd-gallery-main img').forEach(function (img) {
      var parent = img.closest('.category-card, .product-card, .pd-gallery-main');
      if (!parent) return;
      gsap.fromTo(img,
        { scale: 1.08 },
        {
          scale: 1, ease: 'none',
          scrollTrigger: {
            trigger: parent,
            start: 'top bottom', end: 'bottom top',
            scrub: 1.5
          }
        }
      );
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 7. PROGRESS BARS
  // ═══════════════════════════════════════════════════════════════

  function initProgressBars() {
    gsap.utils.toArray('.pd-bar-fill').forEach(function (bar) {
      var width = bar.style.width || '0%';
      bar.style.width = '0%';
      gsap.to(bar, {
        width: width, duration: 1.2, ease: 'power3.out',
        scrollTrigger: { trigger: bar, start: 'top 90%', once: true }
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 8. LEGACY REVEAL SYSTEM (backward compatible)
  // ═══════════════════════════════════════════════════════════════

  function initLegacyReveals() {
    gsap.utils.toArray('.reveal-right-premium, .reveal-fade-up, .reveal-left, .reveal-right, .reveal-scale').forEach(function (el) {
      if (el.dataset.reveal) return;

      var isRight = el.classList.contains('reveal-right-premium') || el.classList.contains('reveal-right');
      var isLeft = el.classList.contains('reveal-left');
      var isScale = el.classList.contains('reveal-scale');

      var fromVars = { autoAlpha: 0 };
      if (isRight) fromVars.x = 60;
      else if (isLeft) fromVars.x = -60;
      else if (isScale) fromVars.scale = 0.88;
      else fromVars.y = 40;

      var delay = 0;
      for (var d = 1; d <= 5; d++) {
        if (el.classList.contains('reveal-delay-' + d)) { delay = d * 0.15; break; }
      }

      gsap.set(el, { autoAlpha: 1 });
      gsap.fromTo(el, fromVars, {
        x: 0, y: 0, scale: 1, autoAlpha: 1, filter: 'blur(0px)',
        duration: 0.95, ease: 'expo.out', delay: delay,
        scrollTrigger: { trigger: el, start: 'top 86%', once: true }
      });
    });

    gsap.utils.toArray('.footer-reveal').forEach(function (el) {
      if (el.dataset.reveal) return;
      gsap.set(el, { autoAlpha: 1 });
      gsap.fromTo(el,
        { y: 40, autoAlpha: 0 },
        { y: 0, autoAlpha: 1, duration: 1, ease: 'expo.out',
          scrollTrigger: { trigger: el, start: 'top 90%', once: true }
        }
      );
    });

    gsap.utils.toArray('.text-reveal').forEach(function (el) {
      var inner = el.querySelector('.text-reveal-inner');
      if (!inner) return;
      gsap.set(el, { autoAlpha: 1 });
      gsap.fromTo(inner,
        { yPercent: 110, autoAlpha: 0 },
        { yPercent: 0, autoAlpha: 1, duration: 1, ease: 'power4.out',
          scrollTrigger: { trigger: el, start: 'top 85%', once: true }
        }
      );
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 9. HERO SLIDE ENTRANCE — Left/Right directional per slide
  // ═══════════════════════════════════════════════════════════════

  var heroEntrancePresets = [
    // Slide 0 — Left-biased (Flagship)
    { headline: 'slide-left-far', subline: 'slide-right', cta: 'slide-left' },
    // Slide 1 — Right-biased (Cloud Stride)
    { headline: 'slide-right-far', subline: 'slide-left', cta: 'slide-right' },
    // Slide 2 — Center-left (Midnight)
    { headline: 'slide-left-far', subline: 'slide-right', cta: 'slide-left-soft' },
  ];

  function animateHeroSlide(slideIndex) {
    var slides = document.querySelectorAll('.hero-slide');
    var slide = slides[slideIndex];
    if (!slide) return;

    var dirs = heroEntrancePresets[slideIndex % heroEntrancePresets.length];
    var text = slide.querySelector('.hero-slide-text');
    if (!text) return;

    gsap.set(text, { autoAlpha: 1 });

    var items = [
      { el: text.querySelector('.hero-headline'), key: dirs.headline, delay: 0 },
      { el: text.querySelector('.hero-subline'), key: dirs.subline, delay: 0.16 },
      { el: text.querySelector('.hero-cta-group'), key: dirs.cta, delay: 0.32 },
    ];

    items.forEach(function (item) {
      if (!item.el) return;
      var preset = revealPresets[item.key];
      if (!preset) return;
      gsap.set(item.el, { clearProps: 'all' });
      gsap.fromTo(item.el, preset.from, {
        x: 0, y: 0, autoAlpha: 1, filter: 'blur(0px)',
        duration: 0.95,
        ease: 'power4.out',
        delay: item.delay
      });
    });
  }

  function initHeroEntrance() {
    document.body.classList.add('page-load');
    var heroSwiperEl = document.querySelector('.hero-swiper');
    if (!heroSwiperEl) return;

    // Listen for hero entrance triggers
    document.addEventListener('heroAnimateSlide', function (e) {
      animateHeroSlide(e.detail.index);
    });

    // Animate first slide on load
    animateHeroSlide(0);
  }

  // ═══════════════════════════════════════════════════════════════
  // 10. 3D TILT — data-tilt on cards
  // ═══════════════════════════════════════════════════════════════

  function initTilt() {
    if (reducedMotion || window.matchMedia('(pointer: coarse)').matches) return;

    gsap.utils.toArray('[data-tilt]').forEach(function (card) {
      var strength = Number(card.dataset.tilt || 8);
      var scale = Number(card.dataset.tiltScale || 1.02);
      var speed = Number(card.dataset.tiltSpeed || 0.3);

      card.addEventListener('pointermove', function (e) {
        var rect = card.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width;
        var y = (e.clientY - rect.top) / rect.height;
        var tiltX = (y - 0.5) * -strength;
        var tiltY = (x - 0.5) * strength;
        gsap.to(card, {
          rotationX: tiltX, rotationY: tiltY, scale: scale,
          duration: speed, ease: 'power3.out',
          transformPerspective: 800
        });
      });

      card.addEventListener('pointerleave', function () {
        gsap.to(card, {
          rotationX: 0, rotationY: 0, scale: 1,
          duration: 0.5, ease: 'power3.out'
        });
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 11. PARALLAX — data-parallax on images
  // ═══════════════════════════════════════════════════════════════

  function initParallaxAttr() {
    if (reducedMotion) return;

    gsap.utils.toArray('[data-parallax]').forEach(function (el) {
      var speed = Number(el.dataset.parallaxSpeed || 0.2);
      var section = el.closest('[data-parallax-section]') || el.parentElement;
      gsap.to(el, {
        y: function () { return window.innerHeight * speed * -1; },
        ease: 'none',
        scrollTrigger: {
          trigger: section, start: 'top bottom', end: 'bottom top',
          scrub: 1.2, invalidateOnRefresh: true
        }
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 12. IMAGE ZOOM — data-image-zoom on product images
  // ═══════════════════════════════════════════════════════════════

  function initImageZoom() {
    if (reducedMotion || window.matchMedia('(pointer: coarse)').matches) return;

    gsap.utils.toArray('[data-image-zoom]').forEach(function (wrap) {
      var img = wrap.querySelector('img') || wrap;
      var target = img.tagName === 'IMG' ? img : wrap;

      wrap.addEventListener('pointerenter', function () {
        gsap.to(target, {
          scale: 1.08, duration: 0.7, ease: 'power3.out',
          overwrite: 'auto'
        });
      });

      wrap.addEventListener('pointerleave', function () {
        gsap.to(target, {
          scale: 1, duration: 0.7, ease: 'power3.out',
          overwrite: 'auto'
        });
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 13. COUNTUP — data-countup animated counters
  // ═══════════════════════════════════════════════════════════════

  function initCountup() {
    if (reducedMotion) return;

    gsap.utils.toArray('[data-countup]').forEach(function (el) {
      var target = Number(el.dataset.countup) || 0;
      var suffix = el.dataset.countupSuffix || '';
      var prefix = el.dataset.countupPrefix || '';
      var decimals = el.dataset.countupDecimals !== undefined ? Number(el.dataset.countupDecimals) : 0;

      var obj = { val: 0 };
      gsap.to(obj, {
        val: target,
        duration: 2,
        ease: 'power3.out',
        scrollTrigger: { trigger: el, start: 'top 85%', once: true },
        onUpdate: function () {
          el.textContent = prefix + obj.val.toFixed(decimals) + suffix;
        }
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 14. SCRUB HORIZONTAL — data-scrub-horizontal galleries
  // ═══════════════════════════════════════════════════════════════

  function initScrubHorizontal() {
    if (reducedMotion) return;

    gsap.utils.toArray('[data-scrub-horizontal]').forEach(function (section) {
      var track = section.querySelector('[data-scrub-track]');
      if (!track) return;

      var wrap = section.querySelector('[data-scrub-wrap]') || track;
      gsap.to(track, {
        x: function () { return -(track.scrollWidth - wrap.offsetWidth); },
        ease: 'none',
        scrollTrigger: {
          trigger: section, start: 'top top',
          end: function () { return '+=' + track.scrollWidth; },
          scrub: 1, pin: true, anticipatePin: 1,
          invalidateOnRefresh: true
        }
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 15. STICKY PIN — data-sticky-pin pinned sections
  // ═══════════════════════════════════════════════════════════════

  function initStickyPin() {
    if (reducedMotion) return;

    gsap.utils.toArray('[data-sticky-pin]').forEach(function (section) {
      var height = section.dataset.stickyPin || '100%';
      ScrollTrigger.create({
        trigger: section, start: 'top top',
        end: function () { return '+=' + (height === '100%' ? window.innerHeight : height); },
        pin: true, anticipatePin: 1,
        invalidateOnRefresh: true
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 16. PARALLAX LAYER — data-parallax-layer depth layers
  // ═══════════════════════════════════════════════════════════════

  function initParallaxLayer() {
    if (reducedMotion) return;

    gsap.utils.toArray('[data-parallax-layer]').forEach(function (layer) {
      var depth = Number(layer.dataset.parallaxLayer || 0.15);
      var section = layer.closest('[data-parallax-section]') || layer.parentElement;
      gsap.to(layer, {
        y: function () { return window.innerHeight * depth * -1; },
        ease: 'none',
        scrollTrigger: {
          trigger: section, start: 'top bottom', end: 'bottom top',
          scrub: 1.2, invalidateOnRefresh: true
        }
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // 17. BUTTON RIPPLE
  // ═══════════════════════════════════════════════════════════════

  function initButtonRipple() {
    document.querySelectorAll('.btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        var rect = btn.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        var ripple = document.createElement('span');
        ripple.style.cssText =
          'position:absolute;border-radius:50%;background:rgba(255,255,255,0.3);' +
          'width:0;height:0;left:' + x + 'px;top:' + y + 'px;' +
          'transform:translate(-50%,-50%);pointer-events:none;z-index:1;';
        btn.appendChild(ripple);
        var size = Math.max(rect.width, rect.height) * 2;
        ripple.animate([
          { width: '0px', height: '0px', opacity: 0.5 },
          { width: size + 'px', height: size + 'px', opacity: 0 }
        ], { duration: 600, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' });
        setTimeout(function () { ripple.remove(); }, 600);
      });
    });
  }

  // ═══════════════════════════════════════════════════════════════
  // INIT
  // ═══════════════════════════════════════════════════════════════

  // NOTE: Smooth anchor scroll is handled by main.js — do NOT add it here
  // to avoid double-fire of scrollIntoView on anchor link clicks.

  function init() {
    initHeroEntrance();
    initTextReveals();
    initScrollReveals();
    initImageReveals();
    initSectionSnapping();
    initMagnetic();
    initImageParallax();
    initProgressBars();
    initTilt();
    initParallaxAttr();
    initImageZoom();
    initCountup();
    initScrubHorizontal();
    initStickyPin();
    initParallaxLayer();
    initButtonRipple();

    ScrollTrigger.refresh();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

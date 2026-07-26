(function(w) {
  'use strict';
  var R = w.PhantomRenderer = w.PhantomRenderer || {};
  var CR = R.ComponentRenderer;

  var DEFAULT_TPL =
    '<a href="{{URL}}" class="category-card" data-tilt data-reveal-item>' +
      '<div class="category-card-bg">' +
        '<img loading="lazy" src="{{IMAGE}}" alt="{{NAME}}">' +
        '<div class="category-card-overlay"></div>' +
      '</div>' +
      '<div class="category-card-content">' +
        '<span class="category-count">{{COUNT}}</span>' +
        '<h3 class="category-name">{{NAME}}</h3>' +
        '<span class="category-cta">{{CTA}} <i class="fas fa-arrow-right"></i></span>' +
      '</div>' +
    '</a>';

  R.CategoryCard = {
    template: DEFAULT_TPL,

    setTemplate: function(tpl) {
      this.template = tpl;
    },

    render: function(data) {
      return CR.renderTemplate(this.template, {
        URL: CR.sanitizeUrl(data.url),
        IMAGE: data.image || '',
        NAME: CR.escapeHtml(data.name),
        COUNT: (data.count || 0) + ' items',
        CTA: 'Shop ' + CR.escapeHtml(data.name),
      });
    },

    renderAll: function(categories) {
      var self = this;
      return categories.map(function(c) { return self.render(c); }).join('');
    }
  };
})(window);

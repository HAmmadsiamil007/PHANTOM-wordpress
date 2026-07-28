(function($) {
  'use strict';

  var wizard = {
    currentStep: 0,
    steps: phantomWizard.steps,
    stepKeys: Object.keys(phantomWizard.steps),

    init: function() {
      this.render();
    },

    render: function() {
      var html = '<div class="wizard-progress">';
      $.each(this.stepKeys, $.proxy(function(i, key) {
        var cls = i === this.currentStep ? 'active' : (i < this.currentStep ? 'done' : '');
        html += '<div class="step ' + cls + '">' + this.steps[key].title + '</div>';
      }, this));
      html += '</div>';
      html += '<div class="wizard-step" id="wizard-body"></div>';
      $('#phantom-wizard-root').html(html);
      this.renderStep();
    },

    renderStep: function() {
      var key = this.stepKeys[this.currentStep];
      switch (key) {
        case 'welcome': this.renderWelcome(); break;
        case 'pack': this.renderPack(); break;
        case 'content': this.renderContent(); break;
        case 'complete': this.renderComplete(); break;
      }
    },

    renderWelcome: function() {
      var html = '<h2>Welcome to Phantom Core</h2>';
      html += '<p>This wizard will help you set up your site in just a few steps. You\'ll choose a template pack, import demo content, and be ready to go.</p>';
      html += '<div class="wizard-actions"><button class="button button-primary" id="wizard-start">Get Started</button></div>';
      $('#wizard-body').html(html);
      $('#wizard-start').on('click', $.proxy(function() { this.next(); }, this));
    },

    renderPack: function() {
      var html = '<h2>Choose a Template Pack</h2><p>Select the visual style for your site. You can change this later.</p>';
      html += '<div class="wizard-pack-grid" id="pack-grid"></div>';
      html += '<div class="wizard-actions"><button class="button" id="wizard-back">Back</button><button class="button button-primary" id="wizard-pack-next" disabled>Next</button></div>';
      $('#wizard-body').html(html);
      $('#wizard-back').on('click', $.proxy(function() { this.prev(); }, this));
      this.loadPacks();
    },

    loadPacks: function() {
      $.post(phantomWizard.ajaxUrl, {
        action: 'phantom_wizard_step',
        step: 'get_packs',
        nonce: phantomWizard.nonce
      }, $.proxy(function(res) {
        if (res.success) {
          var html = '';
          $.each(res.data.packs, function(slug, name) {
            html += '<div class="wizard-pack-card" data-pack="' + slug + '"><h3>' + name + '</h3></div>';
          });
          $('#pack-grid').html(html);
          $('.wizard-pack-card').on('click', function() {
            $('.wizard-pack-card').removeClass('selected');
            $(this).addClass('selected');
            $('#wizard-pack-next').prop('disabled', false).data('pack', $(this).data('pack'));
          });
        }
      }, this), 'json');
    },

    renderContent: function() {
      var html = '<h2>Import Demo Content</h2><p>We\'ll create sample pages, products, and blog posts so you can see how everything looks.</p>';
      html += '<div class="wizard-actions"><button class="button" id="wizard-back">Back</button><button class="button button-primary" id="wizard-import">Import Content</button><button class="button" id="wizard-skip">Skip</button></div>';
      html += '<div class="wizard-result" id="import-result"></div>';
      $('#wizard-body').html(html);
      $('#wizard-back').on('click', $.proxy(function() { this.prev(); }, this));
      $('#wizard-skip').on('click', $.proxy(function() { this.next(); }, this));
      $('#wizard-import').on('click', $.proxy(function() { this.importContent(); }, this));
    },

    importContent: function() {
      var $btn = $('#wizard-import').prop('disabled', true).text('Importing...');
      $.post(phantomWizard.ajaxUrl, {
        action: 'phantom_wizard_step',
        step: 'generate_content',
        nonce: phantomWizard.nonce
      }, $.proxy(function(res) {
        if (res.success) {
          var html = '<div class="success"><p>Content imported successfully!</p><ul>';
          $.each(res.data, function(key, val) {
            html += '<li><strong>' + key + ':</strong> ' + (Array.isArray(val) ? val.length : JSON.stringify(val)) + '</li>';
          });
          html += '</ul></div>';
          $('#import-result').html(html);
          var pack = $('#wizard-pack-next').data('pack');
          if (pack) {
            $.post(phantomWizard.ajaxUrl, {
              action: 'phantom_wizard_step',
              step: 'set_pack',
              nonce: phantomWizard.nonce,
              data: { pack: pack }
            });
          }
          setTimeout($.proxy(function() { this.next(); }, this), 1500);
        } else {
          $('#import-result').html('<div class="fail"><p>Import failed: ' + res.data.message + '</p></div>');
          $btn.prop('disabled', false).text('Retry');
        }
      }, this), 'json');
    },

    renderComplete: function() {
      var html = '<h2>All Set!</h2><p>Your Phantom Core site is ready. You can now explore your new site or visit the admin dashboard.</p>';
      html += '<div class="wizard-actions"><a href="' + phantomWizard.ajaxUrl.replace('admin-ajax.php', '') + '" class="button button-primary">Visit Dashboard</a>';
      $.post(phantomWizard.ajaxUrl, { action: 'phantom_wizard_step', step: 'complete', nonce: phantomWizard.nonce });
      $('#wizard-body').html(html);
    },

    next: function() {
      var pack = $('#wizard-pack-next').data('pack');
      if (pack && this.stepKeys[this.currentStep] === 'pack') {
        $.post(phantomWizard.ajaxUrl, {
          action: 'phantom_wizard_step',
          step: 'set_pack',
          nonce: phantomWizard.nonce,
          data: { pack: pack }
        });
      }
      if (this.currentStep < this.stepKeys.length - 1) {
        this.currentStep++;
        this.render();
      }
    },

    prev: function() {
      if (this.currentStep > 0) {
        this.currentStep--;
        this.render();
      }
    }
  };

  $(document).ready(function() { wizard.init(); });
})(jQuery);

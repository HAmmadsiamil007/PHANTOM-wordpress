(function($) {
	var frame;

	$('.phantom-core-color-picker').wpColorPicker();

	$(document).on('click', '.phantom-core-image-upload', function(e) {
		e.preventDefault();
		var self = $(this);
		var container = self.closest('.phantom-core-image-field');
		var input = container.find('.phantom-core-image-input');
		var preview = container.find('.phantom-core-image-preview');

		if (frame) {
			frame.open();
			return;
		}

		frame = wp.media({
			title: phantomCoreAdmin.mediaTitle || 'Choose Image',
			button: { text: phantomCoreAdmin.mediaButton || 'Use Image' },
			multiple: false
		});

		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			input.val(attachment.id);
			preview.html('<img src="' + attachment.url + '" alt="" />');
			container.find('.phantom-core-image-remove').show();
		});

		frame.open();
	});

	$(document).on('click', '.phantom-core-image-remove', function(e) {
		e.preventDefault();
		var container = $(this).closest('.phantom-core-image-field');
		container.find('.phantom-core-image-input').val('');
		container.find('.phantom-core-image-preview').empty();
		$(this).hide();
	});

	$(document).on('click', '.phantom-core-image-upload-small', function(e) {
		e.preventDefault();
		var self = $(this);
		var input = self.siblings('input');

		var smallFrame = wp.media({
			title: phantomCoreAdmin.mediaTitle || 'Choose Image',
			button: { text: phantomCoreAdmin.mediaButton || 'Use Image' },
			multiple: false
		});

		smallFrame.on('select', function() {
			var attachment = smallFrame.state().get('selection').first().toJSON();
			input.val(attachment.url);
		});

		smallFrame.open();
	});

	$(document).on('click', '.phantom-core-repeater-add', function(e) {
		e.preventDefault();
		var repeater = $(this).closest('.phantom-core-repeater');
		var template = repeater.find('.phantom-core-repeater-template').html();
		var rowsContainer = repeater.find('.phantom-core-repeater-rows');
		var index = rowsContainer.children().length;

		var html = template.replace(/\{\{INDEX\}\}/g, index);
		var $row = $(html);

		rowsContainer.append($row);
		$row.find('.phantom-core-color-picker').wpColorPicker();
	});

	$(document).on('click', '.phantom-core-repeater-remove', function(e) {
		e.preventDefault();
		$(this).closest('.phantom-core-repeater-row').remove();
	});

	$('.phantom-core-repeater-rows').sortable({
		handle: '.phantom-core-repeater-row',
		placeholder: 'phantom-core-repeater-row ui-state-highlight',
		forcePlaceholderSize: true
	});

	function toggleDependentFields() {
		$('.phantom-core-dependent').each(function() {
			var $el = $(this);
			var dependOn = $el.data('depend-on');
			var dependValue = String($el.data('depend-value'));
			var $parentField = $el.closest('tr').prevAll('tr').find('#' + dependOn);

			if (!$parentField.length) {
				$parentField = $('#' + dependOn);
			}

			var show = false;

			if ($parentField.is(':checkbox')) {
				var current = $parentField.prop('checked') ? '1' : '0';
				var values = dependValue.split(',');
				show = values.indexOf(current) !== -1;
			} else if ($parentField.is('select')) {
				var current = String($parentField.val());
				var values = dependValue.split(',');
				show = values.indexOf(current) !== -1;
			} else {
				var current = String($parentField.val());
				var values = dependValue.split(',');
				show = values.indexOf(current) !== -1;
			}

			$el.toggle(show);
		});
	}

	toggleDependentFields();

	$(document).on('change', 'input, select', function() {
		toggleDependentFields();
	});

	$('.phantom-core-code-editor').each(function() {
		var $textarea = $(this);
		var mode = $textarea.data('editor-mode') || 'text/html';

		wp.codeEditor.initialize($textarea, {
			codemirror: {
				mode: mode,
				lineNumbers: true,
				autoCloseTags: true,
				matchBrackets: true,
				indentUnit: 2,
				tabSize: 2,
				lineWrapping: true,
				extraKeys: {
					'Ctrl-S': function() {
						$textarea.closest('form').find('button[type="submit"]').click();
					}
				}
			}
		});
	});
})(jQuery);

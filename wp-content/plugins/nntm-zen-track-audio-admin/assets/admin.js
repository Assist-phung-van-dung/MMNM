(function ($) {
	'use strict';

	$(function () {
		var $field = $('[data-nntm-zen-audio-field]');
		if (!$field.length || !window.wp || !wp.media) {
			return;
		}

		var frame;
		var $id = $field.find('[data-nntm-audio-id]');
		var $current = $field.find('[data-nntm-audio-current]');
		var $name = $field.find('[data-nntm-audio-name]');
		var $preview = $field.find('[data-nntm-audio-preview]');
		var $empty = $field.find('[data-nntm-audio-empty]');
		var $select = $field.find('[data-nntm-audio-select]');
		var $remove = $field.find('[data-nntm-audio-remove]');

		function renderAttachment(attachment) {
			var filename = attachment.filename || attachment.title || ('#' + attachment.id);
			$id.val(attachment.id || '');
			$name.text(filename);
			$preview.attr('src', attachment.url || '');
			$current.prop('hidden', false);
			$empty.prop('hidden', true);
			$remove.prop('hidden', false);
			$select.text('Đổi tệp âm thanh');
		}

		$select.on('click', function (event) {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Chọn tệp âm thanh',
				button: { text: 'Dùng tệp này' },
				library: { type: 'audio' },
				multiple: false
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first();
				if (attachment) {
					renderAttachment(attachment.toJSON());
				}
			});

			frame.open();
		});

		$remove.on('click', function (event) {
			event.preventDefault();
			$id.val('');
			$preview.attr('src', '');
			$name.text('');
			$current.prop('hidden', true);
			$empty.prop('hidden', false);
			$remove.prop('hidden', true);
			$select.text('Chọn tệp âm thanh');
		});
	});
})(jQuery);

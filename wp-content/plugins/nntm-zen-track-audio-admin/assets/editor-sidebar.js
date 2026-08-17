(function (wp) {
	'use strict';

	if (!wp || !wp.plugins || !wp.editPost || !wp.data || !wp.element || !wp.components || !wp.blockEditor) {
		return;
	}

	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var Spinner = wp.components.Spinner;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var __ = wp.i18n.__;
	var META_KEY = '_nntm_track_audio';

	function AudioPanel() {
		var editorState = useSelect(function (select) {
			var editor = select('core/editor');
			var postType = editor.getCurrentPostType();
			var meta = editor.getEditedPostAttribute('meta') || {};
			var audioId = parseInt(meta[META_KEY] || 0, 10) || 0;
			return {
				postType: postType,
				meta: meta,
				audioId: audioId
			};
		}, []);

		var media = useSelect(function (select) {
			if (!editorState.audioId) {
				return null;
			}
			return select('core').getMedia(editorState.audioId);
		}, [editorState.audioId]);

		var editPost = useDispatch('core/editor').editPost;

		if ('nntm_zen_track' !== editorState.postType) {
			return null;
		}

		function setAudio(mediaObject) {
			var id = mediaObject && mediaObject.id ? parseInt(mediaObject.id, 10) : 0;
			var nextMeta = Object.assign({}, editorState.meta);
			nextMeta[META_KEY] = id;
			editPost({ meta: nextMeta });
		}

		var content = [];

		if (editorState.audioId && !media) {
			content.push(el('div', { key: 'loading', className: 'nntm-zen-audio-loading' }, el(Spinner)));
		}

		if (media) {
			var mime = media.mime_type || media.mime || '';
			var sourceUrl = media.source_url || '';
			var filename = media.media_details && media.media_details.file ? media.media_details.file.split('/').pop() : (media.title && media.title.rendered ? media.title.rendered : ('#' + media.id));

			if (mime && 0 !== mime.indexOf('audio/')) {
				content.push(el(Notice, { key: 'invalid', status: 'error', isDismissible: false }, __('Attachment đã chọn không phải file audio.', 'nntm-zen-track-audio')));
			} else {
				content.push(
					el('div', { key: 'current', className: 'nntm-zen-audio-editor-current' },
						el('strong', null, filename),
						sourceUrl ? el('audio', { controls: true, preload: 'metadata', src: sourceUrl }) : null
					)
				);
			}
		} else if (!editorState.audioId) {
			content.push(el('p', { key: 'empty', className: 'components-base-control__help' }, __('Chưa chọn tệp âm thanh.', 'nntm-zen-track-audio')));
		}

		content.push(
			el(MediaUploadCheck, { key: 'upload' },
				el(MediaUpload, {
					onSelect: setAudio,
					allowedTypes: ['audio'],
					multiple: false,
					value: editorState.audioId || undefined,
					render: function (args) {
						return el(Button, { variant: 'secondary', onClick: args.open }, editorState.audioId ? __('Đổi tệp âm thanh', 'nntm-zen-track-audio') : __('Chọn tệp âm thanh', 'nntm-zen-track-audio'));
					}
				})
			)
		);

		if (editorState.audioId) {
			content.push(el(Button, { key: 'remove', isDestructive: true, variant: 'tertiary', onClick: function () { setAudio(null); } }, __('Gỡ tệp âm thanh', 'nntm-zen-track-audio')));
		}

		content.push(el('p', { key: 'help', className: 'components-base-control__help nntm-zen-audio-help' }, __('Tên bài lấy từ ô Tiêu đề. Hình bài lấy từ Ảnh đại diện. Block Thiền Đường lấy file audio từ _nntm_track_audio và tự ghi lượt nghe.', 'nntm-zen-track-audio')));

		return el(PluginDocumentSettingPanel, {
			name: 'nntm-zen-track-audio-panel',
			title: __('Tệp âm thanh', 'nntm-zen-track-audio'),
			className: 'nntm-zen-track-audio-panel'
		}, content);
	}

	wp.plugins.registerPlugin('nntm-zen-track-audio-admin', {
		render: AudioPanel,
		icon: 'format-audio'
	});
})(window.wp);

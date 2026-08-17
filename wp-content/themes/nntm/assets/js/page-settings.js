(function (wp) {
    'use strict';

    if (
        !wp ||
        !wp.plugins ||
        !wp.editPost ||
        !wp.editPost.PluginDocumentSettingPanel ||
        !wp.components ||
        !wp.data ||
        !wp.element
    ) {
        return;
    }

    var createElement = wp.element.createElement;
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var CheckboxControl = wp.components.CheckboxControl;
    var Notice = wp.components.Notice;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var __ = wp.i18n.__;

    function NntmPageSettings() {
        var postType = useSelect(function (select) {
            return select('core/editor').getCurrentPostType();
        }, []);

        var meta = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);

        var editor = useDispatch('core/editor');

        if ('page' !== postType) {
            return null;
        }

        var isHidden = Boolean(meta._nntm_hide_page_title);

        return createElement(
            PluginDocumentSettingPanel,
            {
                name: 'nntm-page-settings',
                title: __('NNTM Page Settings', 'nntm'),
                className: 'nntm-page-settings'
            },
            createElement(CheckboxControl, {
                label: __('Ẩn tiêu đề trang', 'nntm'),
                help: __('Ẩn H1 do template Page sinh ra. Tiêu đề nằm bên trong các block vẫn được giữ.', 'nntm'),
                checked: isHidden,
                onChange: function (checked) {
                    editor.editPost({
                        meta: Object.assign({}, meta, {
                            _nntm_hide_page_title: Boolean(checked)
                        })
                    });
                }
            }),
            isHidden
                ? createElement(
                    Notice,
                    {
                        status: 'info',
                        isDismissible: false
                    },
                    __('Tiêu đề Page sẽ không hiển thị ngoài frontend sau khi cập nhật trang.', 'nntm')
                )
                : null
        );
    }

    registerPlugin('nntm-page-settings', {
        render: NntmPageSettings
    });
})(window.wp);

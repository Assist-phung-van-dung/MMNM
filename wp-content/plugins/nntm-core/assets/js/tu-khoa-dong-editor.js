/**
 * Ô tích "Bật từ khoá động" trong thanh bên trình soạn thảo (phiếu khảo sát câu 33:
 * hiệu ứng chỉ chạy ở trang trọng điểm do BQT chọn).
 */
(function (wp, cfg) {
    'use strict';

    // WP 6.6+ chuyển panel sang wp.editor; wp.editPost chỉ còn bản cũ kèm cảnh báo deprecated.
    var PluginDocumentSettingPanel = wp && (
        (wp.editor && wp.editor.PluginDocumentSettingPanel) ||
        (wp.editPost && wp.editPost.PluginDocumentSettingPanel)
    );

    if (!wp || !wp.plugins || !PluginDocumentSettingPanel || !wp.components || !wp.data || !wp.element) {
        return;
    }

    cfg = cfg || {};

    var createElement = wp.element.createElement;
    var registerPlugin = wp.plugins.registerPlugin;
    var CheckboxControl = wp.components.CheckboxControl;
    var Notice = wp.components.Notice;
    var ExternalLink = wp.components.ExternalLink;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;

    var KHOA = '_nntm_tu_khoa_dong';
    var postTypes = cfg.postTypes || [];

    function NntmTuKhoaDongPanel() {
        var postType = useSelect(function (select) {
            return select('core/editor').getCurrentPostType();
        }, []);

        var meta = useSelect(function (select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        }, []);

        var editor = useDispatch('core/editor');

        if (postTypes.indexOf(postType) === -1) {
            return null;
        }

        var dangBat = Boolean(meta[KHOA]);
        var soTuKhoa = parseInt(cfg.soTuKhoa, 10) || 0;

        return createElement(
            PluginDocumentSettingPanel,
            {
                name: 'nntm-tu-khoa-dong',
                title: __('Từ khoá động', 'nntm'),
                className: 'nntm-tu-khoa-dong-panel'
            },
            createElement(CheckboxControl, {
                label: __('Bật từ khoá động trên trang này', 'nntm'),
                help: __('Rê chuột vào từ khoá trong nội dung sẽ hiện hình minh hoạ. Chỉ bật ở trang trọng điểm.', 'nntm'),
                checked: dangBat,
                onChange: function (checked) {
                    var moi = {};
                    moi[KHOA] = Boolean(checked);
                    editor.editPost({ meta: Object.assign({}, meta, moi) });
                }
            }),
            dangBat && soTuKhoa === 0
                ? createElement(
                    Notice,
                    { status: 'warning', isDismissible: false },
                    __('Chưa có từ khoá nào có hình hoặc mô tả — bật lên cũng chưa thấy gì.', 'nntm')
                )
                : null,
            createElement(
                'p',
                null,
                soTuKhoa > 0
                    /* translators: %d: số từ khoá */
                    ? sprintf(__('Đang có %d từ khoá. ', 'nntm'), soTuKhoa)
                    : null,
                cfg.quanLyUrl
                    ? createElement(ExternalLink, { href: cfg.quanLyUrl }, __('Quản lý danh sách từ khoá', 'nntm'))
                    : null
            )
        );
    }

    registerPlugin('nntm-tu-khoa-dong', {
        render: NntmTuKhoaDongPanel
    });
})(window.wp, window.nntmTuKhoaDongEditor);

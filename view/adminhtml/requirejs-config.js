/**
 * MB "Vienas bitas" (Magetrend.com)
 *
 * @category MageTrend
 * @package  Magetend/PdfTemplates
 * @author   Edvinas Stulpinas <edwin@magetrend.com>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     https://www.magetrend.com/magento-2-pdf-invoice-pro
 */
var config = {
    map: {
        '*': {
            mtemail_button: 'Magetrend_PdfTemplates/js/system/config/button',
            mtEditor_jquery: 'Magetrend_PdfTemplates/js/mteditor/jquery-2.1.3',
            mtEditor_bootstrap: 'Magetrend_PdfTemplates/js/mteditor/bootstrap.min',
            mtEditor_cookie: 'Magetrend_PdfTemplates/js/mteditor/jquery.cookie',
            mtEditor_jquery_ui: 'Magetrend_PdfTemplates/js/mteditor/jquery-ui',
            mtEditor_ui_widget: 'Magetrend_PdfTemplates/js/mteditor/jquery.ui.widget',
            mtEditor_iframe_transport: 'Magetrend_PdfTemplates/js/mteditor/jquery.iframe-transport',
            mtEditor_file_upload: 'Magetrend_PdfTemplates/js/mteditor/jquery.fileupload',
            mtEditor_helper: 'Magetrend_PdfTemplates/js/mteditor/text_edit_helper',
            mtEditor_color_picker: 'Magetrend_PdfTemplates/js/mteditor/colorpicker',
            mtEditor_popup: 'Magetrend_PdfTemplates/js/mteditor/popup',
            mtEditor_save_helper: 'Magetrend_PdfTemplates/js/mteditor/helper/save',
            mtEditor_metis_menu: 'Magetrend_PdfTemplates/js/mteditor/jquery.metisMenu',
            mtEditor_editor: 'Magetrend_PdfTemplates/js/mteditor/editor'
        },
        shim: {
            'mtEditor_bootstrap': {
                deps: ['jquery']
            },
            'mtEditor_cookie': {
                deps: ['jquery']
            },
            'mtEditor_jquery_ui': {
                deps: ['jquery']
            },
            'mtEditor_ui_widget': {
                deps: ['jquery']
            },
            'mtEditor_iframe_transport': {
                deps: ['jquery']
            },
            'mtEditor_file_upload': {
                deps: ['jquery']
            },
            'mtEditor_helper': {
                deps: ['jquery']
            },
            'mtEditor_color_picker': {
                deps: ['jquery']
            },
            'mtEditor_popup': {
                deps: ['jquery']
            },
            'mtEditor_save_helper': {
                deps: ['jquery']
            },
            'mtEditor_metis_menu': {
                deps: ['jquery']
            },
            'mtEditor_editor': {
                deps: ['jquery']
            }
        }
    }
};
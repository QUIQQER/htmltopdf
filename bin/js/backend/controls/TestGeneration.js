/**
 * Test PDF / image generation
 *
 * @module package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',

    //'css!package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration.css'

], function (QUIControl, QUIButton, QUIConfirm, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/htmltopdf';

    /**
     * @class controls/usersAndGroups/Input
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration',

        Binds: [
            '$onImport',
            '$generateTestDocument'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$BtnPdf   = null;
            this.$BtnImage = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on inject
         */
        $onImport: function () {
            var Input = this.getElm();

            Input.type = 'hidden';

            this.$BtnImage = new QUIButton({
                text        : QUILocale.get(lg, 'controls.TestGeneration.btn_image.text'),
                textimage   : 'fa fa-file-image-o',
                documentType: 'image',
                events      : {
                    onClick: this.$generateTestDocument
                }
            }).inject(Input, 'after');

            this.$BtnPdf = new QUIButton({
                text        : QUILocale.get(lg, 'controls.TestGeneration.btn_pdf.text'),
                textimage   : 'fa fa-file-pdf-o',
                documentType: 'pdf',
                styles      : {
                    'margin-right': 10
                },
                events      : {
                    onClick: this.$generateTestDocument
                }
            }).inject(Input, 'after');
        },

        /**
         * Generate a test PDF
         *
         * @param {*} Btn - QUIButton control
         * @return Promise<void>
         */
        $generateTestDocument: async function (Btn) {
            const id   = 'download-document-test';
            const type = Btn.getAttribute('documentType');

            const startDownload = () => {
                new Element('iframe', {
                    src   : URL_OPT_DIR + 'quiqqer/htmltopdf/bin/backend/test.php?type=' + type,
                    id    : id,
                    styles: {
                        position: 'absolute',
                        top     : -200,
                        left    : -200,
                        width   : 50,
                        height  : 50
                    }
                }).inject(document.body);

                (() => {
                    document.getElements('#' + id).destroy();

                    this.$BtnPdf.enable();
                    this.$BtnImage.enable();
                }).delay(10000);
            };

            this.$BtnPdf.disable();
            this.$BtnImage.disable();

            try {
                switch (type) {
                    case 'image':
                        await this.$testProviderImage();
                        break;

                    default:
                        await this.$testProviderPdf();
                }

                startDownload();
            } catch (e) {
                let error = e;

                if (typeof e === 'object' && 'getMessage' in e) {
                    error = e.getMessage();
                }

                new QUIConfirm({
                    maxHeight: 450,
                    maxWidth : 500,

                    autoclose         : true,
                    backgroundClosable: false,

                    information: QUILocale.get(lg, 'controls.TestGeneration.error.information', {
                        error: error
                    }),
                    title      : QUILocale.get(lg, 'controls.TestGeneration.error.title'),
                    texticon   : 'fa fa-exclamation-triangle',
                    text       : QUILocale.get(lg, 'controls.TestGeneration.error.text'),
                    icon       : 'fa fa-exclamation-triangle',

                    cancel_button: false,
                    ok_button    : {
                        text     : QUILocale.get(lg, 'controls.TestGeneration.error.btn_submit'),
                        textimage: 'icon-ok fa fa-check'
                    },
                    events       : {
                        onOpen: () => {
                            this.$BtnPdf.enable();
                            this.$BtnImage.enable();
                        }
                    }
                }).open();
            }
        },

        $testProviderImage: async function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('package_quiqqer_htmltopdf_ajax_backend_testProviderImage', function (error) {
                    if (error) {
                        reject(error);
                    }

                    resolve();
                }, {
                    'package': 'quiqqer/htmltopdf',
                    onError  : reject
                });
            });
        },

        $testProviderPdf: async function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('package_quiqqer_htmltopdf_ajax_backend_testProviderPdf', function (error) {
                    if (error !== null) {
                        reject(error);
                        return;
                    }

                    resolve();
                }, {
                    'package': 'quiqqer/htmltopdf',
                    onError  : reject
                });
            });
        }
    });
});

/**
 * Test PDF generation
 *
 * @module package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale',

    //'css!package/quiqqer/htmltopdf/bin/js/backend/controls/TestGeneration.css'

], function (QUIControl, QUIButton, QUIAjax, QUILocale) {
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

            this.$Btn = null;

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

            this.$Btn = new QUIButton({
                text     : QUILocale.get(lg, 'controls.TestGeneration.btn.text'),
                textimage: 'fa fa-print',
                events   : {
                    onClick: this.$generateTestDocument
                }
            }).inject(Input, 'after');
        },

        /**
         * Generate a test PDF
         */
        $generateTestDocument: function () {
            var self = this;
            var id   = 'download-document-test';

            this.$Btn.disable();

            new Element('iframe', {
                src   : URL_OPT_DIR + 'quiqqer/htmltopdf/bin/test.php?',
                id    : id,
                styles: {
                    position: 'absolute',
                    top     : -200,
                    left    : -200,
                    width   : 50,
                    height  : 50
                }
            }).inject(document.body);

            (function () {
                document.getElements('#' + id).destroy();
                self.$Btn.enable();
            }).delay(2000, this);
        }
    });
});

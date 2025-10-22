/**
 * Select for installed CAPTCHA modules
 *
 * @module package/quiqqer/htmltopdf/bin/js/backend/controls/ProviderSelect
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/htmltopdf/bin/js/backend/controls/ProviderSelect', [

    'qui/controls/buttons/Select',
    'qui/controls/loader/Loader',

    'Ajax'

], function (QUISelect, QUILoader, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUISelect,
        Type   : 'package/quiqqer/htmltopdf/bin/js/backend/controls/ProviderSelect',

        Binds: [
            '$onInject',
            '$onChange',
            '$load'
        ],

        options: {
            showIcons: false
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();

            this.addEvents({
                onImport: this.$onImport,
                onChange: this.$onChange
            });
        },

        /**
         * event: onInject
         */
        $onImport: async function () {
            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            const Select = this.create();
            Select.inject(this.$Input, 'after');

            this.Loader.inject(Select);

            Select.addClass('field-container-field');

            await this.$load();
        },

        /**
         * Load captcha list and add entries
         */
        $load: async function () {
            this.Loader.show();

            const providers = await this.$getProviderList();

            for (const provider of providers) {
                this.appendChild(
                    provider.title,
                    provider.class
                );
            }

            if (this.$Input.value !== '') {
                this.setValue(this.$Input.value);
            }

            this.Loader.hide();
        },

        /**
         * @return {Promise<Array<{title: string, class: string}>>}
         */
        $getProviderList: async function() {
            return new Promise((resolve, reject) => {
                QUIAjax.get(
                    'package_quiqqer_htmltopdf_ajax_backend_getProviderList',
                    resolve,
                    {
                        'package': 'quiqqer/htmltopdf',
                        onError  : reject
                    }
                );
            });
        },

        /**
         * event: onChange
         */
        $onChange: function () {
            this.$Input.value = this.getValue();
        }
    });
});
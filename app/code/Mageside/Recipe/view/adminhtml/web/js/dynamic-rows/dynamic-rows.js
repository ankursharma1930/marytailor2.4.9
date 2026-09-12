define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows'
], function (dynamicRows) {
    'use strict';

    return dynamicRows.extend({
        defaults: {
            isUseDefault: '',
            listens: {
                'isUseDefault': 'toggleUseDefault'
            }
        },

        initialize: function () {
            this._super();
            this.isUseDefault(this.disabled());
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('isUseDefault serviceDisabled');
            return this;
        },

        /**
         * @returns boolean
         */
        hasService: function () {
            return this.service && this.service.template;
        },

        /**
         * @param {Boolean} state
         */
        toggleUseDefault: function (state) {
            this.disabled(state);

            if (this.source && this.hasService()) {
                this.source.set('data.use_default.' + this.index, Number(state));
            }
        }
    });
});

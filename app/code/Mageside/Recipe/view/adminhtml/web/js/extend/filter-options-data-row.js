/**
 * Copyright © Mageside, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'mageUtils',
    'uiLayout',
    'Magento_Ui/js/form/element/abstract'
], function (_, utils, layout, abstract) {
    'use strict';

    return abstract.extend({
        defaults: {

        },

        setInitialValue: function () {
            this._super();

            var record = this.source.get(this.parentScope);

            if (record.hasOwnProperty('label_is_default')) {
                this.isUseDefault(!!Number(record.label_is_default));
            }

            return this;
        },

        /**
         * @param {Boolean} state
         */
        toggleUseDefault: function (state) {
            this.disabled(state);

            if (this.source && this.hasService()) {
                var record = this.source.get(this.parentScope);
                if (record.hasOwnProperty('label_is_default') && !this.hasOwnProperty('visibleListener')) {
                    this.source.set(this.parentScope + '.label_is_default', record.label_is_default);
                } else {
                    this.source.set(this.parentScope + '.label_is_default', Number(state));
                }
            }
        },
        getRecordId: function () {
            return this.source.get(this.parentScope + '.record_id');
        }
    });
});

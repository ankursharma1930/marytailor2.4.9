
/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/form/element/text'
], function ($, text) {
    'use strict';

    return text.extend({
        initObservable: function () {
            this._super();

            return this;
        }
    });
});

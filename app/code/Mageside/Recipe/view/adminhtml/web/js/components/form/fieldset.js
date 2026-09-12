/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'Mageside_Recipe/js/components/form/strategy'
], function (Fieldset, strategy) {
    'use strict';

    return Fieldset.extend(strategy).extend(
        {
            defaults: {
                openOnShow: true
            },

            /**
             * Toggle visibility state.
             */
            toggleVisibility: function () {
                var visible = this._super();

                if (this.openOnShow) {
                    this.opened(visible);
                }
            }
        }
    );
});

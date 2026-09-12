/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'Mageside_Recipe/js/components/form/strategy'
], function (Element, strategy) {
    'use strict';

    return Element.extend(strategy);

});
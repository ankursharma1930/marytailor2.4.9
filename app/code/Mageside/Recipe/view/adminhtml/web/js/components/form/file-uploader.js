/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define([
    'underscore',
    'mageUtils',
    'Magento_Ui/js/form/element/file-uploader',
    'Mageside_Recipe/js/components/form/strategy',
    'prototype'
], function (_, utils, Uploader, strategy) {
    'use strict';

    return Uploader.extend(strategy).extend({
        initConfig: function () {
            var uid = utils.uniqueid(),
                name,
                valueUpdate,
                prepareName,
                scope;

            this._super();
            scope   = this.dataScope;
            var regular = /\./;
            var result = scope.match(regular);
            if (result) {
                prepareName = scope.split('.').slice(1);
                name = prepareName.pop();
            } else {
                name = scope;
            }

            valueUpdate = this.showFallbackReset ? 'afterkeydown' : this.valueUpdate;

            this.uploaderConfig.url = this.uploaderConfig.url + 'file/' + name;

            _.extend(this, {
                uid: uid,
                noticeId: 'notice-' + uid,
                inputName: utils.serializeName(name),
                valueUpdate: valueUpdate
            });
            return this;
        }
    });
});

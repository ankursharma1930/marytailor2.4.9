/**
 * Copyright © Mageside. All rights reserved.
 * See MS-LICENSE.txt for license details.
 */
define(['underscore', 'uiRegistry'], function (_, registry) {
    'use strict';

    return {
        defaults: {
            valuesForOptions: {},
            valuesForOptionsDisable: {},
            visibleToggles: {},
            disableToggles: {},
            listens: {}
        },

        /** @inheritdoc */
        initConfig: function (config) {
            this.initDataLink(config);
            this._super();

            return this;
        },

        initialize: function () {
            this._super();
            this.toggleVisibility();
            this.toggleDisable();

            return this;
        },

        /**
         * Adding listeners to config.
         */
        initDataLink: function (config) {
            this.addListens(config.visibleToggles, 'toggleVisibility');
            this.addListens(config.disableToggles, 'toggleDisable');
        },

        addListens: function (list, call) {
            _.each(list, function (settings, path) {
                if (this.listens.hasOwnProperty(path + ':value')) {
                    this.listens[path + ':value'] = this.listens[path + ':value'] + ' ' + call;
                } else {
                    this.listens[path + ':value'] = call;
                }
            }, this.constructor.defaults);
        },

        /**
         * Toggle visibility state.
         */
        toggleVisibility: function () {
            var visible = _.every(this.visibleToggles, function (settings, path) {
                return this.checkStatement(path, settings, {property: 'visible', inverse: true});
            }, this);

            if (Object.keys(this.visibleToggles).length > 0) {
                this.visible(visible);
            }

            return visible;
        },

        /**
         * Toggle disable state.
         */
        toggleDisable: function () {
            var disabled = _.some(this.disableToggles, function (settings, path) {
                return this.checkStatement(path, settings);
            }, this);

            if (Object.keys(this.disableToggles).length > 0) {
                this.disabled(disabled);
            }

            return disabled;
        },

        checkStatement: function (path, settings, adds) {
            var result = false,
                visibleCheck = true,
                addsProperty,
                isSelected,
                inverse,
                addsCheck;

            registry.get(path, function(source) {
                if (source === undefined) {
                    result = false;
                } else {
                    adds = adds || {};
                    if (adds.hasOwnProperty('property')) {
                        addsProperty = adds.property;
                        addsCheck = source[adds.property]();
                        if (adds.inverse ? !addsCheck : addsCheck) {
                            visibleCheck = false;
                        }
                    }

                    if (addsProperty === 'visible' && visibleCheck) {
                        isSelected = source.value() in settings.options;
                        inverse = settings.hasOwnProperty('inverse') ? settings.inverse : false;

                        result = inverse ? !isSelected : isSelected
                    }
                }
            });

            return result;
        }
    };
});
